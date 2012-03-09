<?php
namespace org\opencomb\platform ;

use org\jecat\framework\fs\Folder;
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
 * 
 * === 平台特点 ===
 * 
 * == 扩展驱动模式 ==
 * 传统的互联网系统产品（例如：WorldPress, Discuz!）也支持插件机制，第三方开发者可以为系统提供插件，以增强系统的功能。\
 * 但是在技术实现上，却都无一例外地采用了“肥”主体系统，“瘦”插件模块的架构，功能和服务的实现主要依赖主系统，而主系统在设计风格上是“闭合”的，插件无法进入主系统以及影响主系统的行为。
 * 
 * 因此，做为系统中的“二等公民”（甚至是“第三等公民”），第三方插件能够发挥的余地实际上是非常有限的。\
 * 这些产品的完善和市场成长，完全依赖主系统开发团队单方面的努力。
 * 
 * 蜂巢平台采用了不同的架构：系统将所有的功能的实现交给了平台的“扩展”。\
 * 扩展成为了系统中的一等公民，平台不参与任何具体需求的实现。
 * 
 * 第三方扩展向平台提供功能，整个系统能够向最终用户提供的互联网服务，完全取决于平台上所安装了哪些扩展。
 * 
 * 并且，在安全机制的授权下，一个扩展可以全面地影响另一个扩展的行为：增加、修改，以及禁用其他扩展所提供的功能。
 * 
 * 扩展的授权方式，由第三方开发者自由决定。所以有些扩展是开源和免费的，而有些扩展是被保护的，并且收取费用。
 * 
 * 蜂巢平台的“扩展驱动模式”释放了第三方开发者的创造力，在这个开放的体系中，所有开发团队的资源都被整合在了一起。推动事业前进的，是利益一致的多方团队。
 * 
 * 
 * 
 * 
 */
class Platform extends Application
{
	const version = '0.2.0.0' ;
	const data_version = '0.2' ;
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
	 * @return org\jecat\framework\util\Version
	 */
	public function dataVersion($bString=false)
	{
		if($bString)
		{
			return self::data_version ;
		}
		else
		{
			if( !$this->aDataVersion )
			{
				$this->aDataVersion = Version::FromString(self::data_version) ;
			}
			return $this->aDataVersion ;
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
	
	/**
	 * 平台的系统签名，受平台、框架、扩展及其版本的影响
	 */
	public function systemSignature()
	{
		$sSrc = 'framework:' . \org\jecat\framework\VERSION . '/'
			. 'platform:' . self::version . '/' ;
		
		foreach($this->extensions()->enableExtensionMetainfoIterator() as $aExtMeta)
		{
			$sSrc.= 'extension:'.$aExtMeta->name().':'.$aExtMeta->version()->__toString().'/' ;
		}
		
		return md5($sSrc) ;
	}
	
	/**
	 * @return org\jecat\cache\ICache 
	 */
	public function cache()
	{
		if(!$this->aCache)
		{
			$this->aCache = new FSCache(Folder::singleton()->findFolder('data/cache/platform',Folder::FIND_AUTO_CREATE)) ;
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
	private $aDataVersion ;
	private $aVersionCompat ;
	private $aCache ;
}

?>
