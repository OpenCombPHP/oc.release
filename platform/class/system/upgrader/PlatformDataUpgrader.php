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
			$this->upgrade() ;
			
			// restore system
			$aPlatformShutdowner->restore() ;
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
			throw new Exception('no upgrade path');
		}
		
		foreach($arrPath as $sPath){
			$aUpdater = new $sPath ;
			$aUpdater->process();
		}
		
		$aSetting = Setting::singleton() ;
		$aSetting->setItem('/platform','data_version',$aToVersion->toString());
	}
	
	private function currentVersion(){
		return Platform::singleton ()->dataVersion ();
	}
	
	private function dataVersion(){
		$aSetting = Setting::singleton() ;
		$sVersion = $aSetting->item('/platform','data_version','0.0') ;
		return Version::fromString($sVersion);
	}
}
