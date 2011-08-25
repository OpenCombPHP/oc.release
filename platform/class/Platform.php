<?php
namespace oc ;

use oc\ext\ExtensionManager;
use oc\ext\ExtensionMetainfo;
use oc\resrc\ResourceManager;
use jc\resrc\HtmlResourcePool;
use jc\ui\xhtml\UIFactory ;
use jc\mvc\view\UIFactory as MvcUIFactory ;
use oc\ui\SourceFileManager;
use jc\system\Application;
use oc\system\PlatformFactory ;

class Platform extends Application
{
	public function __construct($sAppDir)
	{
		parent::__construct($sAppDir) ;

		if( !Application::singleton(false) )
		{
			Application::setSingleton($this) ;
		}

		$aAppFactory = new PlatformFactory() ;
		$aAppFactory->build($this) ;

		// app dir
		$aFs = $this->fileSystem() ;
		$this->setApplicationDir($sAppDir) ;

		// 模板文件
		UIFactory::singleton()->compilerManager()->compilerByName('jc\\ui\xhtml\\Macro')->setSubCompiler(
				'/', "oc\\ui\\xhtml\\compiler\\PathMacroCompiler"
		) ;
		MvcUIFactory::singleton()->compilerManager()->compilerByName('jc\\ui\xhtml\\Macro')->setSubCompiler(
				'/', "oc\\ui\\xhtml\\compiler\\PathMacroCompiler"
		) ;
		
		$aSrcFileMgr = new SourceFileManager() ;
		UIFactory::singleton()->setSourceFileManager($aSrcFileMgr) ;
		MvcUIFactory::singleton()->setSourceFileManager($aSrcFileMgr) ;
		
		$aSrcFileMgr->addFolder($aFs->findFolder('/platform/ui/template'),'oc') ;
		$aSrcFileMgr->addFolder($aFs->findFolder('/framework/src/template'),'jc') ;
		
		// css/js 资源
		$aJsMgr = new ResourceManager() ;
		$aCssMgr = new ResourceManager() ;
		HtmlResourcePool::setSingleton( new HtmlResourcePool($aJsMgr,$aCssMgr) ) ;
		
		$aJsMgr->addFolder($aFs->findFolder('/platform/ui/js'),'oc') ;
		$aCssMgr->addFolder($aFs->findFolder('/platform/ui/css'),'oc') ;
		$aCssMgr->addFolder($aFs->findFolder('/framework/src/style'),'jc') ;
		
		// 默认的控制器
		$aAccessRouter = $this->accessRouter() ;
		$aAccessRouter->setDefaultController('oc\\base\\DefaultController') ;
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
			$this->aExtensionManager = new ExtensionManager() ;
		}
		return $this->aExtensionManager ;
	}
	
	private $sExtensionsFolder = 'extensions' ;
	private $aExtensionManager ;
	private $aStaticPageManager ;
}

?>