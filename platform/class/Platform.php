<?php
namespace oc ;

use oc\ext\ExtensionManager;
use oc\ext\ExtensionMetainfo;
use oc\resrc\ResourceManager;
use jc\resrc\HtmlResourcePool;
use jc\ui\xhtml\UIFactory ;
use jc\system\Application;
use oc\system\PlatformFactory ;

class Platform extends Application
{
	public function init()
	{
		$this->extensions() ;
		//foreach($this->extensions()->installExtension() as )
	}
	
	public function loadExtension(ExtensionMetainfo $aExtMeta)
	{
		$sPlatformDir = $this->applicationDir() ;

		$sName = $aExtMeta->name() ;

		// 加载类包
		$this->classLoader()->addPackage(
				$aExtMeta->classPackageNamespace()
				, $aExtMeta->classPackageFolder()->path()
				, $aExtMeta->classCompiledPackageFolder()->path()
		) ;
		
		// 注册ui模板目录
		if( $aTemplateFolder=$aExtMeta->resourceUiTemplateFolder() )
		{
			UIFactory::singleton()->sourceFileManager()->addFolder($aTemplateFolder, $sName) ;
		}
		
		// 注册 js/css 目录
		if($aJsFolder=$aExtMeta->resourceUiJsFolder())
		{
			HtmlResourcePool::singleton()->javaScriptFileManager()->addFolder($aJsFolder,$sName) ;
		}
		if($aCssFolder=$aExtMeta->resourceUiCssFolder())
		{
			HtmlResourcePool::singleton()->cssFileManager()->addFolder($aCssFolder,$sName) ;
		}
		
		$sClass = $aExtMeta->className() ;		
		$aExtension = new $sClass($aExtMeta) ;
		$aExtension->setApplication($this) ;
				
		$aExtension->load() ;
		
		$this->extensions()->add($aExtension) ;
		
		return $aExtension ;
	}
	
	public function extensionsUrl()
	{
		return $this->sExtensionsFolder.'/' ;
	}
	public function extensionsDir()
	{
		return $this->applicationDir() . $this->sExtensionsFolder . '/' ;
	}
	
	/**
	 * @return oc\ext\ExtensionManager 
	 */
	public function extensions()
	{
		if( !$this->aExtensionManager )
		{
			$this->aExtensionManager = new ExtensionManager($this->setting()) ;
		}
		return $this->aExtensionManager ;
	}
	
	private $sExtensionsFolder = 'extensions' ;
	private $aExtensionManager ;
	private $aStaticPageManager ;
}

?>