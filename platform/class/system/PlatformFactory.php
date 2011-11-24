<?php
namespace org\opencomb\system ;

use org\jecat\framework\fs\FileSystem;
use org\jecat\framework\bean\BeanFactory;
use org\opencomb\Platform;
use org\jecat\framework\lang\Object;
use org\jecat\framework\system\Application;
use org\opencomb\resrc\ResourceManager;
use org\jecat\framework\resrc\HtmlResourcePool;
use org\jecat\framework\ui\xhtml\UIFactory ;
use org\jecat\framework\mvc\view\UIFactory as MvcUIFactory ;
use org\opencomb\ui\SourceFileManager;
use org\jecat\framework\system\HttpAppFactory;
use org\jecat\framework\system\CoreApplication;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\system\AccessRouter as JcAccessRouter;

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
		UIFactory::singleton()->compilerManager()->compilerByName('org\\jecat\\framework\\ui\xhtml\\Macro')->setSubCompiler(
				'/', "org\\opencomb\\ui\\xhtml\\compiler\\PathMacroCompiler"
		) ;
		MvcUIFactory::singleton()->compilerManager()->compilerByName('org\\jecat\\framework\\ui\xhtml\\Macro')->setSubCompiler(
				'/', "org\\opencomb\\ui\\xhtml\\compiler\\PathMacroCompiler"
		) ;
		
		// 模板文件
		$aSrcFileMgr = new SourceFileManager() ;
		UIFactory::singleton()->setSourceFileManager($aSrcFileMgr) ;
		MvcUIFactory::singleton()->setSourceFileManager($aSrcFileMgr) ;
		
		$aSrcFileMgr->addFolder(
				$aFileSystem->findFolder('/framework/template')
				, $aFileSystem->findFolder('/data/compiled/template/framework',FileSystem::FIND_AUTO_CREATE)
				, 'org.jecat.framework'
		) ;
		$aSrcFileMgr->addFolder(
				$aFileSystem->findFolder('/platform/template')
				, $aFileSystem->findFolder('/data/compiled/template/platform',FileSystem::FIND_AUTO_CREATE)
				, 'org.opencomb'
		) ;
		
		// public folder
		$aPublicFolders = $aPlatform->publicFolders() ;
		$aPublicFolders->addFolder($aFileSystem->findFolder('/public/platform'),'org.opencomb') ;
		HtmlResourcePool::setSingleton( new HtmlResourcePool($aPublicFolders) ) ;
		
		// bean classes
		BeanFactory::singleton()->registerBeanClass('org\\opencomb\\mvc\\model\\db\\orm\\Prototype','prototype') ;
		BeanFactory::singleton()->registerBeanClass('org\\opencomb\\mvc\\model\\db\\orm\\Association','association') ;
						
		return $aPlatform ;
	}
	
	public function createClassLoader()
	{
		$aClassLoader = parent::createClassLoader() ;
		
		// class
		$aClassLoader->addPackage( 'org\\opencomb', '/platform/class' ) ;
		$aClassLoader->enableClassCompile(true) ;
		
		return $aClassLoader ;
	}
	
	public function createAccessRouter()
	{
		$aAccessRouter = parent::createAccessRouter() ;
		$aAccessRouter->setDefaultController('org\\opencomb\\mvc\\controller\\DefaultController') ;
		return $aAccessRouter ;
	}
}

?>