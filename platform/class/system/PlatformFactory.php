<?php
namespace oc\system ;

use oc\Platform;
use jc\lang\Object;
use jc\system\Application;
use oc\resrc\ResourceManager;
use jc\resrc\HtmlResourcePool;
use jc\ui\xhtml\UIFactory ;
use jc\mvc\view\UIFactory as MvcUIFactory ;
use oc\ui\SourceFileManager;
use jc\system\HttpAppFactory;
use jc\system\CoreApplication;
use jc\lang\oop\ClassLoader;

class PlatformFactory extends HttpAppFactory
{
	static public function singleton($bCreateNew=true)
	{
		return Object::singleton($bCreateNew,null,__CLASS__) ;
	}
	
	public function create($sAppDirPath)
	{
		$aPlatform = new Platform($sAppDirPath) ;
		
		$this->build($aPlatform) ;
	
		// 设置单件
		if( !Application::singleton(false) )
		{
			Application::setSingleton($aPlatform) ;
		}
		
		// app dir
		$aFs = $aPlatform->fileSystem() ;
		$aPlatform->setApplicationDir($sAppDirPath) ;

		// 模板引擎宏
		UIFactory::singleton()->compilerManager()->compilerByName('jc\\ui\xhtml\\Macro')->setSubCompiler(
				'/', "oc\\ui\\xhtml\\compiler\\PathMacroCompiler"
		) ;
		MvcUIFactory::singleton()->compilerManager()->compilerByName('jc\\ui\xhtml\\Macro')->setSubCompiler(
				'/', "oc\\ui\\xhtml\\compiler\\PathMacroCompiler"
		) ;
		
		// 模板文件
		$aSrcFileMgr = new SourceFileManager() ;
		UIFactory::singleton()->setSourceFileManager($aSrcFileMgr) ;
		MvcUIFactory::singleton()->setSourceFileManager($aSrcFileMgr) ;
		
		$aSrcFileMgr->addFolder($aFs->findFolder('/platform/template'),$aFs->findFolder('/data/compiled/template/platform'),'oc') ;
		$aSrcFileMgr->addFolder($aFs->findFolder('/framework/src/template'),$aFs->findFolder('/data/compiled/template/framework'),'jc') ;
		
		// css/js 资源
		$aJsMgr = new ResourceManager() ;
		$aCssMgr = new ResourceManager() ;
		HtmlResourcePool::setSingleton( new HtmlResourcePool($aJsMgr,$aCssMgr) ) ;
		
		$aJsMgr->addFolder($aFs->findFolder('/public/platform/js'),'oc') ;
		$aCssMgr->addFolder($aFs->findFolder('/public/platform/css'),'oc') ;
		$aCssMgr->addFolder($aFs->findFolder('/framework/src/style'),'jc') ;
		
		// 默认的控制器
		$aAccessRouter = $aPlatform->accessRouter() ;
		$aAccessRouter->setDefaultController('oc\\mvc\\controller\\DefaultController') ;
		
		// 加载
		

		
		return $aPlatform ;
	}
	
	public function createClassLoader(CoreApplication $aApp)
	{
		$aClassLoader = new ClassLoader(
			$aApp->application()->fileSystem()->findFile("/classpath.php") 
		) ;
		
		// class
		$aClassLoader->addPackage( 'jc', '/framework/src/lib.php', '/data/compiled/class/framework' ) ;
		$aClassLoader->addPackage( 'oc', '/platform/class', '/data/compiled/class/platform' ) ;

		$aClassLoader->enableClassCompile(true) ;
		
		return $aClassLoader ;
	}
	
	public function createAccessRouter(CoreApplication $aApp)
	{
		return new AccessRouter() ;
	}
}

?>