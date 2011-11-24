<?php
namespace oc ;

use jc\setting\Setting;

use jc\lang\oop\ClassLoader;

use jc\util\Version;
use oc\ext\ExtensionManager;
use oc\ext\ExtensionMetainfo;
use oc\resrc\ResourceManager;
use jc\resrc\HtmlResourcePool;
use jc\ui\xhtml\UIFactory ;
use jc\system\Application;
use oc\system\PlatformFactory ;

class Platform extends Application
{
	const version = '0.2.0.0' ;
	
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
	{
		// 加载扩展
		$aExtensions = $this->extensions() ;
		foreach($aExtensions->enableExtensionNameIterator() as $sExtName)
		{
			$aExtensions->loadExtension($sExtName) ;
		}
		
		// 计算/设置 类签名
		$aSetting = Setting::singleton() ;
		$aCompiler = ClassLoader::singleton()->compiler() ;
		if( !$sClassSignture = $aSetting->item('/platform/class','signture') )
		{
			$aSetting->setItem('/platform/class','signture',$aCompiler->strategySignature(true)) ;
		}
		else
		{
			$aCompiler->setStrategySignature($sClassSignture) ;
		}
	}
	
	/**
	 * @return oc\ext\ExtensionManager 
	 */
	public function extensions()
	{
		if( !$this->aExtensionManager )
		{
			$this->aExtensionManager = new ExtensionManager(Setting::singleton()) ;
		}
		return $this->aExtensionManager ;
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
	
	private $sExtensionsFolder = 'extensions' ;
	private $aExtensionManager ;
	private $aStaticPageManager ;
	private $aVersion ;
}

?>