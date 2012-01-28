<?php
namespace org\opencomb\platform ;

use org\jecat\framework\util\VersionCompat;

use org\jecat\framework\fs\FileSystem;
use org\jecat\framework\cache\FSCache;
use org\jecat\framework\setting\Setting;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\util\Version;
use org\jecat\framework\resrc\HtmlResourcePool;
use org\jecat\framework\ui\xhtml\UIFactory ;
use org\jecat\framework\system\Application;
use org\opencomb\platform\ext\ExtensionManager;
use org\opencomb\platform\ext\ExtensionMetainfo;
use org\opencomb\platform\resrc\ResourceManager;
use org\opencomb\platform\system\PlatformFactory ;

/**
 * @wiki /蜂巢/平台
 */
class Platform extends Application
{
	const version = '0.2.0.0' ;
	const version_compat = "" ;
	
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
	
	/**
	 * @return org\jecat\framework\util\Version
	 */
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
	/**
	 * @return org\jecat\framework\util\VersionCompat
	 */
	public function versionCompat()
	{
		if(!$this->aVersionCompat)
		{
			$this->aVersionCompat = new VersionCompat;
			// 当前版本
			$this->aVersionCompat->addCompatibleVersion( $this->version() ) ;
			
			// 其它兼容版本
			if( self::version_compat )
			{
				$this->aVersionCompat->addFromString(self::version_compat) ;
			}
		}
		return $this->aVersionCompat ;
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
	
	public function isDebugging()
	{
		return (bool)Setting::singleton()->item('/platform/debug','stat') ;
	}
	
	private $sExtensionsFolder = 'extensions' ;
	private $aExtensionManager ;
	private $aStaticPageManager ;
	private $aVersion ;
	private $aVersionCompat ;
	private $aCache ;
}

?>
