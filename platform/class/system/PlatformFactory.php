<?php
namespace org\opencomb\platform\system ;

use org\opencomb\platform\ext\Extension;
use org\jecat\framework\session\OriginalSession;
use org\jecat\framework\session\Session;
use org\jecat\framework\db\DB;
use org\jecat\framework\db\driver\PDODriver;
use org\jecat\framework\cache\ICache;
use org\jecat\framework\system\Response;
use org\jecat\framework\system\Request;
use org\jecat\framework\setting\Setting;
use org\jecat\framework\locale\LocaleManager;
use org\jecat\framework\fs\FileSystem;
use org\jecat\framework\bean\BeanFactory;
use org\opencomb\platform\Platform;
use org\jecat\framework\lang\Object;
use org\jecat\framework\system\Application;
use org\opencomb\platform\resrc\ResourceManager;
use org\jecat\framework\resrc\HtmlResourcePool;
use org\jecat\framework\ui\xhtml\UIFactory ;
use org\jecat\framework\mvc\view\UIFactory as MvcUIFactory ;
use org\jecat\framework\ui\SourceFileManager as JcSourceFileManager ;
use org\opencomb\platform\ui\SourceFileManager;
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
		
		// setting
		$aSetting = $this->createSetting($aPlatform) ;
		Setting::setSingleton($aSetting) ;
		
		// 从缓存中恢复 platform ---------------
		if( !$aSetting->item('/platform','serialize',true) or !self::restorePlatformFromCache($aPlatform->cache(),$aPlatform) )
		{
			// 重建 platform
			// --------------------------
			
			// 初始化 class loader
			ClassLoader::setSingleton($this->createClassLoader($aPlatform)) ;
			
			// (request/respone 需要在ClassLoader之后)
			$this->initPlatformRequestResponse($aPlatform) ;
			
			// AccessRouter
			JcAccessRouter::setSingleton($this->createAccessRouter($aPlatform)) ;
			
			// LocalManager
			LocaleManager::setSingleton($this->createLocaleManager($aPlatform)) ;
				
			// 模板文件
			JcSourceFileManager::setSingleton($this->createUISourceFileManager($aFileSystem)) ;
			
			// 初始化系统无须store/restore的部分
			$this->initPlatformUnrestorableSystem($aPlatform,$aFileSystem,$aSetting) ;
			
			// BeanFactory 类别名
			BeanFactory::singleton()->registerBeanClass('org\\opencomb\\platform\\mvc\\model\\db\\orm\\Prototype','prototype') ;
			BeanFactory::singleton()->registerBeanClass('org\\opencomb\\platform\\mvc\\model\\db\\orm\\Association','association') ;
			
			// 加载所有扩展
			$aExtMgr = $aPlatform->extensions() ;
			foreach($aExtMgr->enableExtensionNameIterator() as $sExtName)
			{
				$aExtMgr->loadExtension($sExtName) ;
			}
			
			// 计算 UI template 的编译策略签名
			UIFactory::singleton()->calculateCompileStrategySignture() ;
			
			// store all !
			$this->storePlatformToCache($aPlatform->cache(),$aPlatform) ;
		}			

		else 
		{			
			// 初始化系统无须store/restore的部分
			$this->initPlatformUnrestorableSystem($aPlatform,$aFileSystem,$aSetting) ;
			
			// (request/respone 需要在ClassLoader之后)
			$this->initPlatformRequestResponse($aPlatform) ;
		} 
		
		// 配置 
		ClassLoader::singleton()->setEnableClassCache( Setting::singleton()->item('/platform/class','enableClassPathCache',true) ) ;
		
		// 激活所有扩展
		foreach($aPlatform->extensions()->iterator() as $aExtension)
		{
			$aExtension->active($aPlatform) ;

			// 注册 Extension::flyweight()
			Extension::setFlyweight($aExtension,$aExtension->metainfo()->name()) ;
		}
		
		if($aOriApp)
		{
			Application::switchSingleton($aOriApp) ;
		}
		
		return $aPlatform ;
	}
	
	static private $arrSystemSleepObject = array(
			'org\\jecat\\framework\\lang\\oop\\ClassLoader' ,
			'org\\jecat\\framework\\system\\AccessRouter' ,
			'org\\jecat\\framework\\locale\\LocaleManager' ,
			'org\\jecat\\framework\\setting\\Setting' ,
			'org\\jecat\\framework\\ui\\xhtml\\UIFactory' ,
			'org\\jecat\\framework\\mvc\\view\\UIFactory' ,
			'org\\jecat\\framework\\ui\\SourceFileManager' ,
			'org\\jecat\\framework\\bean\\BeanFactory' ,
			'org\\jecat\\framework\\lang\\aop\\AOP' ,
			'org\\opencomb\\platform\\ext\\ExtensionManager' ,
	) ;
	static public function storePlatformToCache(ICache $aCache,Platform $aPlatform)
	{
		foreach(self::$arrSystemSleepObject as $sClass)
		{
			$aCache->setItem(self::platformObjectCacheStorePath($sClass),$sClass::singleton()) ;
		}
		
		$aCache->setItem(self::platformObjectCacheStorePath("org\\opencomb\\platform\\publicFolder"),$aPlatform->publicFolders()) ;
	}
	static private function restorePlatformFromCache(ICache $aCache,Platform $aPlatform)
	{
		foreach(self::$arrSystemSleepObject as $sClass)
		{
			$arrInstances[$sClass] = $aCache->item( self::platformObjectCacheStorePath($sClass) ) ;
			if( !$arrInstances[$sClass] or !($arrInstances[$sClass] instanceof Object) )
			{
				return false ;
			}
		}
		
		$aPublicFolders = $aCache->item(self::platformObjectCacheStorePath("org\\opencomb\\platform\\publicFolder")) ;
		if( !$aPublicFolders or !($aPublicFolders instanceof Object) )
		{
			return false ;
		}
		
		foreach($arrInstances as $sClass=>$aIns)
		{
			$sClass::setSingleton($aIns) ;
		}
		
		$aPlatform->setPublicFolders($aPublicFolders) ;
		
		return true ;
	}
	static public function clearRestoreCache(Platform $aPlatform)
	{
		$aCache = $aPlatform->cache() ;
		foreach(self::$arrSystemSleepObject as $sClass)
		{
			$aCache->delete( self::platformObjectCacheStorePath($sClass) ) ;
		}
		
		$aCache->delete(self::platformObjectCacheStorePath("org\\opencomb\\platform\\publicFolder")) ;
	}
	static private function platformObjectCacheStorePath($sClass)
	{
		return "/system/objects/".str_replace('\\','.',$sClass) ;
	}
	
	private function initPlatformRequestResponse(Platform $aPlatform)
	{
		// Request
		Request::setSingleton( $this->createRequest($aPlatform) ) ;
		
		// Response
		Response::setSingleton( $this->createResponse($aPlatform) ) ;
	}
	private function initPlatformUnrestorableSystem(Platform $aPlatform,FileSystem $aFileSystem,Setting $aSetting)
	{
		// 数据库
		$sDBConfig = $aSetting->item('/platform/db','config','alpha') ;
		$aDBDriver = new PDODriver(
				$aSetting->item('/platform/db/'.$sDBConfig,'dsn')
				, $aSetting->item('/platform/db/'.$sDBConfig,'username')
				, $aSetting->item('/platform/db/'.$sDBConfig,'password')
				, $aSetting->item('/platform/db/'.$sDBConfig,'options',array(\PDO::MYSQL_ATTR_INIT_COMMAND=>"SET NAMES 'utf8'"))
		) ;
		DB::singleton()->setDriver($aDBDriver) ;

		// 会话
		Session::setSingleton( new OriginalSession() ) ;

		// 模板引擎宏
		UIFactory::singleton()->compilerManager()->compilerByName('org\\jecat\\framework\\ui\xhtml\\Macro')->setSubCompiler(
				'/', "org\\opencomb\\platform\\ui\\xhtml\\compiler\\PathMacroCompiler"
		) ;
		MvcUIFactory::singleton()->compilerManager()->compilerByName('org\\jecat\\framework\\ui\xhtml\\Macro')->setSubCompiler(
				'/', "org\\opencomb\\platform\\ui\\xhtml\\compiler\\PathMacroCompiler"
		) ;
		
		// public folder
		$aPublicFolders = $aPlatform->publicFolders() ;
		$aPublicFolders->addFolder($aFileSystem->findFolder('/public/platform'),'org.opencomb') ;
		HtmlResourcePool::setSingleton( new HtmlResourcePool($aPublicFolders) ) ;
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
		$aClassLoader->addPackage( 'org\\opencomb\\platform', '/platform/class' ) ;
		$aClassLoader->enableClassCompile(true) ;
		
		return $aClassLoader ;
	}
	
	public function createAccessRouter(Platform $aApp)
	{
		$aAccessRouter = parent::createAccessRouter($aApp) ;
		$aAccessRouter->setDefaultController('org\\opencomb\\platform\\mvc\\controller\\DefaultController') ;
		return $aAccessRouter ;
	}
	
	public function createUISourceFileManager(FileSystem $aFileSystem)
	{
		$aSrcFileMgr = new SourceFileManager() ;
		UIFactory::singleton()->setSourceFileManager($aSrcFileMgr) ;
		MvcUIFactory::singleton()->setSourceFileManager($aSrcFileMgr) ;
		
		$aSrcFileMgr->addFolder( $aFileSystem->findFolder('/framework/template'), 'org.jecat.framework' ) ;
		$aSrcFileMgr->addFolder( $aFileSystem->findFolder('/platform/template') , 'org.opencomb' ) ;
		
		return $aSrcFileMgr ;
	}
}

?>