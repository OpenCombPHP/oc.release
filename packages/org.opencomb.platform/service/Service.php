<?php
namespace org\opencomb\platform\service ;

use org\opencomb\platform\Platform;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\setting\Setting;
use org\jecat\framework\system\Application;
use org\opencomb\platform\ext\ExtensionManager;

class Service extends Application
{
	/**
	 * @return Service
	 */
	static public function singleton($bCreateNew=true,$createArgvs=null,$sClass=null)
	{
		return parent::singleton() ;
	}
	static public function setSingleton(self $aInstance=null)
	{
		parent::singleton($aInstance) ;
	}
	
	public function serviceName()
	{
		return $this->sServiceName ;
	}
	public function setServiceName($sName)
	{
		$this->sServiceName = $sName ;
	}
	
	/**
	 * @return org\opencomb\platform\ext\ExtensionManager 
	 */
	public function extensions()
	{
		return ExtensionManager::singleton() ;
	}
	
	/**
	 * 平台签名
	 */
	public function signature()
	{
		$aSetting = Setting::singleton() ;
		if( !$sSignature = $aSetting->value('/service/signature') )
		{
			$sSignature = md5( microtime() . rand(0,100000) ) ;
			$aSetting->setValue('/service/signature',$sSignature) ;
			// $aSetting->saveKey('/service') ;
		}
		
		return $sSignature ;
	}
	
	public function setServiceSetting(array $arrServiceSetting)
	{
		$this->arrServiceSetting =& $arrServiceSetting ;
		$this->setServiceName($arrServiceSetting['name']) ;
	}

	/**
	 * @return org\jecat\framework\fs\Folder
	 */
	public function filesFolder()
	{
		if(!$this->aFilesFolder)
		{
			$this->aFilesFolder = Platform::singleton()->filesFolder()->findFolder($this->serviceName(),Folder::FIND_AUTO_CREATE) ;
		}
		return $this->aFilesFolder ;
	}
	
	public function isEnableDataUpgrader(){
		$aSetting = Setting::singleton() ;
		$bEnableDataUpgrader = $aSetting->value('/service/bEnableDataUpgrader',false);
		return $bEnableDataUpgrader ;
	}
	
	public function setEnableDataUpgrader($b){
		$aSetting = Setting::singleton() ;
		$bEnableDataUpgrader = $aSetting->setValue('/service/bEnableDataUpgrader',(bool)$b);
	}
	
	private $aExtensionManager ;
	private $aDataVersion ;
	private $aFilesFolder ;
	private $arrServiceSetting ;
	private $sServiceName ;
}




