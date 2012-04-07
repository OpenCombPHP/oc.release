<?php
namespace org\opencomb\platform\service ;

use org\jecat\framework\cache\Cache;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\cache\FSCache;
use org\jecat\framework\setting\Setting;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\resrc\HtmlResourcePool;
use org\jecat\framework\ui\xhtml\UIFactory ;
use org\jecat\framework\system\Application;
use org\opencomb\platform\ext\ExtensionManager;
use org\opencomb\platform\ext\ExtensionMetainfo;
use org\opencomb\platform\resrc\ResourceManager;
use org\opencomb\platform\system\PlatformFactory ;


class Service extends Application
{
	/**
	 * @return Platform
	 */
	static public function singleton($bCreateNew=true,$createArgvs=null,$sClass=null)
	{
		return parent::singleton() ;
	}
	static public function setSingleton(self $aInstance=null)
	{
		parent::singleton($aInstance) ;
	}
	
	public function load()
	{}
	
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
		if( !$sSignature = $aSetting->item('/platform','signature') )
		{
			$sSignature = md5( microtime() . rand(0,100000) ) ;
			$aSetting->setItem('/platform','signature',$sSignature) ;
			$aSetting->saveKey('/platform') ;
		}
		
		return $sSignature ;
	}
	
	public function isDebugging()
	{
		if($this->bDebugging===null)
		{
			$this->bDebugging = (bool)Setting::singleton()->item('/platform/debug','stat') ;
		}
		return $this->bDebugging ;
	}
	
	private $sExtensionsFolder = 'extensions' ;
	private $aExtensionManager ;
	private $aStaticPageManager ;
	private $aVersion ;
	private $aDataVersion ;
	private $aVersionCompat ;
	private $aCache ;
	
	private $bDebugging = null ;
}

?>
