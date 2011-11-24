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
use jc\system\AccessRouter as JcAccessRouter;

class PlatformFactory extends HttpAppFactory
{
	static public function singleton($bCreateNew=true)
	{
		return Object::singleton($bCreateNew,null,__CLASS__) ;
	}
	
	public function create($sApplicationRootPath)
	{
		$aPlatform = new Platform() ;

		$this->buildApplication($aPlatform,$sApplicationRootPath) ;
		
		$aFileSystem = FileSystem::singleton() ;

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
				$aFileSystem->findFolder('/framework/src/template')
				, $aFileSystem->findFolder('/data/compiled/template/framework',FileSystem::FIND_AUTO_CREATE)
				, 'jc'
		) ;
		$aSrcFileMgr->addFolder(
				$aFileSystem->findFolder('/platform/template')
				, $aFileSystem->findFolder('/data/compiled/template/platform',FileSystem::FIND_AUTO_CREATE)
				, 'oc'
		) ;
		
		// public folder
		$aPublicFolders = $aPlatform->publicFolders() ;
		$aPublicFolders->addFolder($aFileSystem->findFolder('/public/platform'),'oc') ;
		HtmlResourcePool::setSingleton( new HtmlResourcePool($aPublicFolders) ) ;
		
		// bean classes
		BeanFactory::singleton()->registerBeanClass('oc\\mvc\\model\\db\\orm\\Prototype','prototype') ;
		BeanFactory::singleton()->registerBeanClass('oc\\mvc\\model\\db\\orm\\Association','association') ;
						
		return $aPlatform ;
	}
	
	public function createClassLoader()
	{
		$aClassLoader = parent::createClassLoader() ;
		
		// class
		$aClassLoader->addPackage( 'oc', '/platform/class', '/data/compiled/class/platform' ) ;
		$aClassLoader->enableClassCompile(true) ;
		
		return $aClassLoader ;
	}
	
	public function createAccessRouter()
	{
		$aAccessRouter = parent::createAccessRouter() ;
		$aAccessRouter->setDefaultController('oc\\mvc\\controller\\DefaultController') ;
		return $aAccessRouter ;
	}
}

?>