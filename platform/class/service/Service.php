<?php
namespace org\opencomb\platform\service ;

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
		if( !$sSignature = $aSetting->item('/service','signature') )
		{
			$sSignature = md5( microtime() . rand(0,100000) ) ;
			$aSetting->setItem('/service','signature',$sSignature) ;
			$aSetting->saveKey('/service') ;
		}
		
		return $sSignature ;
	}
	
	public function setServiceSetting(array $arrServiceSetting)
	{
		$this->arrServiceSetting =& $arrServiceSetting ;
	}

	/**
	 * @return org\jecat\framework\fs\Folder
	 */
	public function filesFolder()
	{
		if(!$this->aFilesFolder)
		{
			$this->aFilesFolder = new Folder($this->arrServiceSetting['folder_files']) ;
			$this->aFilesFolder->setHttpUrl($this->arrServiceSetting['folder_files_url']) ;
		}
		return $this->aFilesFolder ;
	}
	
	private $aExtensionManager ;
	private $aDataVersion ;
	private $aFilesFolder ;
	private $arrServiceSetting ;
}



