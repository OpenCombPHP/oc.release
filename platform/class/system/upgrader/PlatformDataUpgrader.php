<?php
namespace org\opencomb\platform\system\upgrader ;

use org\jecat\framework\lang\Object;
use org\jecat\framework\fs\FileSystem;
use org\jecat\framework\util\Version ;
use org\jecat\framework\setting\Setting;
use org\opencomb\platform\Platform ;
use org\opencomb\platform\system\PlatformShutdowner ;
use org\jecat\framework\lang\oop\ClassLoader ;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\message\MessageQueue ;

/*
	org\opencomb\platform\system\upgrader ;
*/
class PlatformDataUpgrader extends Object{
	public function process(){
		$sLockFileName = '/'.basename(__FILE__,".php").'Lock.html' ;
		$aLockFile = FileSystem::singleton()->findFile( $sLockFileName ,FileSystem::FIND_AUTO_CREATE) ;
		
		$aLockRes = fopen($aLockFile->url(false),'w');
		flock($aLockRes,LOCK_EX);
		
		if( self::CheckResult_NeedUpgrade === $this->check() ){
			// shut down system
			$aPlatformShutdowner = PlatformShutdowner :: singleton() ;
			$aPlatformShutdowner->shutdown();
			
			// upgrade
			$aDataVersion = $this->dataVersion () ;
			$aCurrentVersion = $this->currentVersion() ;
			
			try{
				$this->upgrade() ;
			
				// restore system
				$aPlatformShutdowner->restore() ;
				$this->relocation() ;
				fclose($aLockRes);
				exit();
			}catch(Exception $e){
				$aPlatformShutdowner->restore() ;
				$this->relocation() ;
				fclose($aLockRes);
				exit();
				
				throw new Exception('升级过程发生异常',array(),$e);
			}
		}
		
		fclose($aLockRes);
	}
	
	const CheckResult_Error = 'error' ;
	const CheckResult_NeedUpgrade = 'needupgrade' ;
	const CheckResult_Nothing = 'nothing' ;
	public function check(){
		$aFromVersion = $this->dataVersion() ;
		$aToVersion = $this->currentVersion() ;
		
		if($aFromVersion < $aToVersion){
			return self::CheckResult_NeedUpgrade ;
		}else{
			return self::CheckResult_Nothing ;
		}
	}
	
	public function upgrade(){
		$aFromVersion = $this->dataVersion() ;
		$aToVersion = $this->currentVersion() ;
		
		$aClassLoader = ClassLoader::singleton() ;
		$arrUpdateClass = array();
		foreach($aClassLoader->classIterator(__NAMESPACE__) as $sClass){
			if(preg_match('`^'.preg_quote(__NAMESPACE__).'\\\\upgrader_(\d+((_\d+){1,3}))To(\d+((_\d+){1,3}))$`' , $sClass , $arrMatch)){
				$sClassFromVersion = str_replace('_','.',$arrMatch[1]) ;
				$sClassToVersion = str_replace('_','.',$arrMatch[4]) ;
				
				$aClassFromVersion = Version::fromString($sClassFromVersion);
				$aClassToVersion = Version::fromString($sClassToVersion);
				
				$arrUpdateClass [] = array(
					'from' => $aClassFromVersion,
					'to' => $aClassToVersion,
					'class' => $sClass ,
				);
			}
		}
		$arrMap = array();
		foreach($arrUpdateClass as $arrUpdate){
			$sName = $arrUpdate['class'];
			$arrMap[$sName] = array(
				'from' => $arrUpdate['from']->toString(),
				'to' => $arrUpdate['to']->toString(),
			) ;
		}
		
		$aCalc = new CalcPath ;
		
		$arrPath = $aCalc->calc($arrMap,$aFromVersion->toString(),$aToVersion->toString() );
		
		if( false === $arrPath ){
			throw new Exception('未找到合适的升级路径');
		}
		
		$aSetting = Setting::singleton() ;
		$aMessageQueue = $this->messageQueue();
		foreach($arrPath as $sPath){
			$aUpgrader = new $sPath ;
			if( ! $aUpgrader instanceof IUpgrader){
				throw new Exception('Upgrader `%s` 未实现指定接口 ： IUpgrader',$sPath);
			}
			$aUpgrader->process($aMessageQueue);
			$aSetting->setItem('/platform','data_version',$arrMap[$sPath]['to']);
		}
	}
	
	private function currentVersion(){
		return Platform::singleton ()->dataVersion ();
	}
	
	private function dataVersion(){
		$aSetting = Setting::singleton() ;
		$sVersion = $aSetting->item('/platform','data_version','0.0') ;
		return Version::fromString($sVersion);
	}
	
	private function relocation(){
		echo <<<CODE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
		<meta name="keywords" content="" />
		<meta name="description" content="" />
		<title>平台升级程序 - </title>

		<link rel="stylesheet" type="text/css" href="/framework/public/style/style.css" />
		<link rel="stylesheet" type="text/css" href="/framework/public/style/widget/menu.css" />
		<link rel="stylesheet" type="text/css" href="/extensions/coresystem/0.1/public/css/reset.css" />
		<link rel="stylesheet" type="text/css" href="/extensions/coresystem/0.1/public/css/ControlPanelFrame.css" />
	</head>
	<body>
CODE;
		$aMessageQueue = $this->messageQueue () ;
		$aMessageQueue->display();
		
		echo <<<CODE
		<div>升级完毕</div>
		<a href="">刷新网页返回</a>
	</body>
</html>
CODE;
	}
	
	private function messageQueue(){
		if( null === $this->aMessageQueue ){
			$this->aMessageQueue = new MessageQueue;
		}
		return $this->aMessageQueue ;
	}
	
	private $aMessageQueue = null;
}
