<?php
namespace org\opencomb\platform\system\upgrader ;

use org\opencomb\platform\service\Service;

use org\jecat\framework\lang\Object;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\util\Version;
use org\jecat\framework\setting\Setting;
use org\opencomb\platform\Platform;
use org\opencomb\platform\system\PlatformShutdowner;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\message\MessageQueue;

/*
	org\opencomb\platform\system\upgrader ;
*/
class PlatformDataUpgrader extends Object{
	public function process(MessageQueue $aMessageQueue = null ){
		if( null === $aMessageQueue ){
			$this->aMessageQueue = new MessageQueue ;
		}else{
			$this->aMessageQueue = $aMessageQueue ;
		}
		$sLockFileName = '/'.basename(__FILE__,".php").'Lock.html' ;
		$aLockFile = Folder::singleton()->findFile( $sLockFileName ,Folder::FIND_AUTO_CREATE) ;
		
		$aLockRes = fopen($aLockFile->path(),'w');
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
				fclose($aLockRes);
				return TRUE;
			}catch(Exception $e){
				$aPlatformShutdowner->restore() ;
				fclose($aLockRes);
				
				throw new Exception('升级过程发生异常',array(),$e);
			}
		}
		
		fclose($aLockRes);
		$aLockFile->delete() ;
	}
	
	const CheckResult_Error = 'error' ;
	const CheckResult_NeedUpgrade = 'needupgrade' ;
	const CheckResult_Nothing = 'nothing' ;
	public function check(){
		$aFromVersion = $this->dataVersion() ;
		$aToVersion = $this->currentVersion() ;
		
		$nCompare = $aFromVersion->compare($aToVersion) ;
		
		if( 0 !== $nCompare ){
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
			if(preg_match('`^'.preg_quote(__NAMESPACE__).'\\\\upgrader_(\d+((_\d+){0,3}))To(\d+((_\d+){0,3}))$`' , $sClass , $arrMatch)){
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
			throw new Exception(
				'未找到合适的升级路径 : from %s to %s',
				array(
					$aFromVersion,
					$aToVersion,
				)
			);
		}
		
		$aSetting = Service::singleton()->setting() ;
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
		$aSetting = Service::singleton()->setting() ;
		$sVersion = $aSetting->item('/platform','data_version','0.0') ;
		return Version::fromString($sVersion);
	}
	
	public function relocation(){
		echo <<<CODE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
		<meta name="keywords" content="" />
		<meta name="description" content="" />
		<title>平台升级程序 - </title>
		
		<link rel="stylesheet" type="text/css" href="/public/platform/css/platformdataupgrader.css" />
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
		return $this->aMessageQueue ;
	}
	
	private $aMessageQueue = null;
}

