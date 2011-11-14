<?php
namespace oc\system ;

use jc\fs\FileSystem;

use jc\bean\BeanFactory;

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
		
		$aSrcFileMgr->addFolder(
				$aFs->findFolder('/framework/src/template')
				, $aFs->findFolder('/data/compiled/template/framework',FileSystem::FIND_AUTO_CREATE)
				, 'jc'
		) ;
		$aSrcFileMgr->addFolder(
				$aFs->findFolder('/platform/template')
				, $aFs->findFolder('/data/compiled/template/platform',FileSystem::FIND_AUTO_CREATE)
				, 'oc'
		) ;
		
		// public folder
		$aPublicFolders = $aPlatform->publicFolders() ;
		$aPublicFolders->addFolder($aFs->findFolder('/public/platform'),'oc') ;
		HtmlResourcePool::setSingleton( new HtmlResourcePool($aPublicFolders) ) ;
		
		// bean classes
		BeanFactory::singleton()->registerBeanClass('oc\\mvc\\model\\db\\orm\\Prototype','prototype') ;
		
		// 默认的控制器
		$aAccessRouter = $aPlatform->accessRouter() ;
		$aAccessRouter->setDefaultController('oc\\mvc\\controller\\DefaultController') ;
				
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