<?php
namespace org\opencomb\system ;

use org\jecat\framework\cache\ICache;
use org\jecat\framework\system\Response;
use org\jecat\framework\system\Request;
use org\jecat\framework\setting\Setting;
use org\jecat\framework\locale\LocaleManager;
use org\jecat\framework\fs\FileSystem;
use org\jecat\framework\bean\BeanFactory;
use org\opencomb\Platform;
use org\jecat\framework\lang\Object;
use org\jecat\framework\system\Application;
use org\opencomb\resrc\ResourceManager;
use org\jecat\framework\resrc\HtmlResourcePool;
use org\jecat\framework\ui\xhtml\UIFactory ;
use org\jecat\framework\mvc\view\UIFactory as MvcUIFactory ;
use org\jecat\framework\ui\SourceFileManager as JcSourceFileManager ;
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
		
		$aOriApp = Application::switchSingleton($aPlatform) ;

		// filesystem (cache 依赖 filesystem，所以需要首先初始化 fs)
		$aFileSystem = $this->createFileSystem($aPlatform,$sApplicationRootPath) ;
		FileSystem::setSingleton($aFileSystem) ;
			
		// 从缓存中恢复 platform ---------------
		if( !self::restorePlatformFromCache($aPlatform->cache()) )
		{
			// 重建 platform
			// --------------------------
			
			// 初始化 class loader
			ClassLoader::setSingleton($this->createClassLoader($aPlatform)) ;
			
			// AccessRouter
			JcAccessRouter::setSingleton($this->createAccessRouter($aPlatform)) ;
			
			// LocalManager
			LocaleManager::setSingleton($this->createLocaleManager($aPlatform)) ;
			
			// setting
			Setting::setSingleton($this->createSetting($aPlatform)) ;
				
			// 模板文件
			JcSourceFileManager::setSingleton($this->createUISourceFileManager($aFileSystem)) ;
		}			

		
		// 其他初始化 
		// ----------------------
		
		// 模板引擎宏
		UIFactory::singleton()->compilerManager()->compilerByName('org\\jecat\\framework\\ui\xhtml\\Macro')->setSubCompiler(
				'/', "org\\opencomb\\ui\\xhtml\\compiler\\PathMacroCompiler"
		) ;
		MvcUIFactory::singleton()->compilerManager()->compilerByName('org\\jecat\\framework\\ui\xhtml\\Macro')->setSubCompiler(
				'/', "org\\opencomb\\ui\\xhtml\\compiler\\PathMacroCompiler"
		) ;
		
		// public folder
		$aPublicFolders = $aPlatform->publicFolders() ;
		$aPublicFolders->addFolder($aFileSystem->findFolder('/public/platform'),'org.opencomb') ;
		HtmlResourcePool::setSingleton( new HtmlResourcePool($aPublicFolders) ) ;
		
		// bean classes
		BeanFactory::singleton()->registerBeanClass('org\\opencomb\\mvc\\model\\db\\orm\\Prototype','prototype') ;
		BeanFactory::singleton()->registerBeanClass('org\\opencomb\\mvc\\model\\db\\orm\\Association','association') ;
		
		// Request
		Request::setSingleton( $this->createRequest($aPlatform) ) ;
		
		// Response
		Response::setSingleton( $this->createResponse($aPlatform) ) ;
		
		if($aOriApp)
		{
			Application::switchSingleton($aOriApp) ;
		}
		
		return $aPlatform ;
	}
	
	static private $arrSystemSleepObject = array(
			'org\\jecat\\framework\\lang\\oop\\ClassLoader' ,
			'org\\jecat\\framework\\fs\\FileSystem' ,
			'org\\jecat\\framework\\system\\AccessRouter' ,
			'org\\jecat\\framework\\locale\\LocalManager' ,
			'org\\jecat\\framework\\setting\\Setting' ,
			'org\\jecat\\framework\\ui\\xhtml\\UIFactory' ,
			'org\\jecat\\framework\\mvc\\view\\UIFactory' ,
			'org\\jecat\\framework\\ui\\xhtml\\UIFactory' ,
			'org\\jecat\\ui\\SourceFileManager' ,
	) ;
	static public function storePlatformToCache(Cache $aCache)
	{
		foreach(self::$arrSystemSleepObject as $sClass)
		{
			$aCache->setItem(self::platformObjectCacheStorePath($sClass),$sClass::singleton()) ;
		}
	}
	static private function restorePlatformFromCache(ICache $aCache)
	{
		foreach(self::$arrSystemSleepObject as $sClass)
		{
			$arrInstances[$sClass] = $aCache->item( self::platformObjectCacheStorePath($sClass) ) ;
			if( !$arrInstances[$sClass] or !($arrInstances[$sClass] instanceof Object) )
			{
				return false ;
			}
		}
		
		foreach($arrInstances as $sClass=>$aIns)
		{
			$sClass::setSingleton($aIns) ;
		}
		
		return true ;
	}
	static private function platformObjectCacheStorePath($sClass)
	{
		return "/system/objects/".str_replace('\\','.',$sClass) ;
	}
	
	public function createClassLoader(Platform $aApp)
	{
		$aCache = $aApp->cache() ;
		
		if( $aClassLoader=$aCache->item('/system/objects/classLoader') )
		{
			return $aClassLoader ;
		}
		
		// 重建缓存
		$aClassLoader = parent::createClassLoader($aApp) ;
		
		// class
		$aClassLoader->addPackage( 'org\\opencomb', '/platform/class' ) ;
		$aClassLoader->enableClassCompile(true) ;
		
		return $aClassLoader ;
	}
	
	public function createAccessRouter(Platform $aApp)
	{
		$aAccessRouter = parent::createAccessRouter($aApp) ;
		$aAccessRouter->setDefaultController('org\\opencomb\\mvc\\controller\\DefaultController') ;
		return $aAccessRouter ;
	}
	
	public function createUISourceFileManager(FileSystem $aFileSystem)
	{
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
		
		return $aSrcFileMgr ;
	}
}

?>