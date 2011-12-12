<?php
namespace org\opencomb ;

use org\jecat\framework\fs\FileSystem;

use org\jecat\framework\cache\FSCache;

use org\jecat\framework\setting\Setting;

use org\jecat\framework\lang\oop\ClassLoader;

use org\jecat\framework\util\Version;
use org\opencomb\ext\ExtensionManager;
use org\opencomb\ext\ExtensionMetainfo;
use org\opencomb\resrc\ResourceManager;
use org\jecat\framework\resrc\HtmlResourcePool;
use org\jecat\framework\ui\xhtml\UIFactory ;
use org\jecat\framework\system\Application;
use org\opencomb\system\PlatformFactory ;

class Platform extends Application
{
	const version = '0.2.0.0' ;
	
	/**
	 * @return Platform
	 */
	static public function singleton()
	{
		return parent::singleton() ;
	}
	static public function setSingleton(self $aInstance=null)
	{
		parent::singleton($aInstance) ;
	}
	
	public function version($bString=false)
	{
		if($bString)
		{
			return self::version ;
		}
		else
		{
			if( !$this->aVersion )
			{
				$this->aVersion = Version::FromString(self::version) ;
			}
			return $this->aVersion ;
		}
	}
	
	public function load()
	{}
	
	/**
	 * @return org\opencomb\ext\ExtensionManager 
	 */
	public function extensions()
	{
		return ExtensionManager::singleton() ;
	}
	
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
	
	/**
	 * @return org\jecat\cache\ICache 
	 */
	public function cache()
	{
		if(!$this->aCache)
		{
			$this->aCache = new FSCache( FileSystem::singleton()->findFolder('/data/cache/platform',FileSystem::FIND_AUTO_CREATE) ) ;
		}
		return $this->aCache ;
	}
	
	private $sExtensionsFolder = 'extensions' ;
	private $aExtensionManager ;
	private $aStaticPageManager ;
	private $aVersion ;
	private $aCache ;
}

?>