<?php
namespace org\opencomb\platform\system ;

use org\jecat\framework\fs\Folder;

use org\jecat\framework\lang\aop\AOP;
use org\jecat\framework\lang\compile\CompilerFactory;
use org\opencomb\platform\ext\ExtensionLoader;
use org\opencomb\platform\ext\Extension;
use org\jecat\framework\session\Session;
use org\jecat\framework\db\DB;
use org\jecat\framework\db\driver\PDODriver;
use org\jecat\framework\cache\ICache;
use org\jecat\framework\mvc\controller\Response;
use org\jecat\framework\mvc\controller\Request;
use org\jecat\framework\setting\Setting;
use org\jecat\framework\locale\LocaleManager;
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
	static public function singleton($bCreateNew=true,$createArgvs=null,$sClass=null)
	{
		return Object::singleton($bCreateNew,null,__CLASS__) ;
	}
	
	public function create($sApplicationRootPath)
	{
		$aPlatform = new Platform() ;
		
		$aOriApp = Application::switchSingleton($aPlatform) ;

		// filesystem (cache 依赖 filesystem，所以需要首先初始化 fs)
		$aFolder = $this->createFileSystem($aPlatform,$sApplicationRootPath) ;
		Folder::setSingleton($aFolder) ;
		
		// setting
		$aSetting = $this->createSetting($aPlatform) ;
		Setting::setSingleton($aSetting) ;
		
		$aPlatformSerializer = PlatformSerializer::singleton(true,$aPlatform) ;
		
		// 从缓存中恢复 platform ---------------
		if( !$aSetting->item('/platform','serialize',true) or !$aPlatformSerializer->restore() )
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
			JcSourceFileManager::setSingleton($this->createUISourceFileManager($aFolder)) ;
			
			// 初始化系统无须store/restore的部分
			$this->initPlatformUnrestorableSystem($aPlatform,$aFolder,$aSetting) ;
			
			// BeanFactory 类别名
			BeanFactory::singleton()->registerBeanClass('org\\opencomb\\platform\\mvc\\model\\db\\orm\\Prototype','prototype') ;
			BeanFactory::singleton()->registerBeanClass('org\\opencomb\\platform\\mvc\\model\\db\\orm\\Association','association') ;
			
			// store system objects !
			$aPlatformSerializer->addSystemSingletons() ;
			
			// 加载所有扩展
			ExtensionLoader::singleton()->loadAllExtensions($aPlatform,$aPlatform->extensions()) ;
			
			// 计算 UI template 的编译策略签名
			UIFactory::singleton()->calculateCompileStrategySignture() ;			
			
			// 激活所有扩展
			ExtensionLoader::singleton()->enableExtensions($aPlatform,$aPlatform->extensions()) ;
		}

		else 
		{
			// 初始化系统无须store/restore的部分
			$this->initPlatformUnrestorableSystem($aPlatform,$aFolder,$aSetting) ;
			
			// (request/respone 需要在ClassLoader之后)
			$this->initPlatformRequestResponse($aPlatform) ;
			
			// 激活所有扩展
			ExtensionLoader::singleton()->enableExtensions($aPlatform,$aPlatform->extensions()) ;
		} 
		
		// 启用class路径缓存
		ClassLoader::singleton()->setEnableClassCache( Setting::singleton()->item('/platform/class','enableClassPathCache',true) ) ;
				
		if($aOriApp)
		{
			Application::switchSingleton($aOriApp) ;
		}
		
		return $aPlatform ;
	}
	
	private function initPlatformRequestResponse(Platform $aPlatform)
	{
		// Request
		Request::setSingleton( $this->createRequest($aPlatform) ) ;
		
		// Response
		Response::setSingleton( $this->createResponse($aPlatform) ) ;
	}
	private function initPlatformUnrestorableSystem(Platform $aPlatform,Folder $aFolder,Setting $aSetting)
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
		Session::setSingleton( new OcSession() ) ;

		// 模板引擎宏
		UIFactory::singleton()->compilerManager()->compilerByName('org\\jecat\\framework\\ui\xhtml\\Macro')->setSubCompiler(
				'/', "org\\opencomb\\platform\\ui\\xhtml\\compiler\\PathMacroCompiler"
		) ;
		MvcUIFactory::singleton()->compilerManager()->compilerByName('org\\jecat\\framework\\ui\xhtml\\Macro')->setSubCompiler(
				'/', "org\\opencomb\\platform\\ui\\xhtml\\compiler\\PathMacroCompiler"
		) ;
		
		// public folder
		$aPublicFolders = $aPlatform->publicFolders() ;
		$aPublicFolders->addFolder($aFolder->findFolder('/public/platform'),'org.opencomb') ;
		HtmlResourcePool::setSingleton( new HtmlResourcePool($aPublicFolders) ) ;
	}
	
	public function createClassLoader(Platform $aApp)
	{
		$aCache = $aApp->cache() ;
		
		// 从缓存中恢复
		if( $aClassLoader=$aCache->item('/system/objects/classLoader') )
		{
			return $aClassLoader ;
		}
		
		// 重建对像
		$aClassLoader = parent::createClassLoader($aApp) ;
		
		// platform class
		$aClassLoader->addPackage( 'org\\opencomb\\platform', Folder::singleton()->findFolder('platform/class') ) ;
		
		// 类编译包
		$aClassLoader->addPackage( 'org\\opencomb\\platform', Folder::singleton()->findFolder('data/compiled/class',Folder::FIND_AUTO_CREATE), ClassLoader::compiled ) ;
		
		
		return $aClassLoader ;
	}
	
	public function createAccessRouter(Platform $aApp)
	{
		$aAccessRouter = parent::createAccessRouter($aApp) ;
		$aAccessRouter->setDefaultController('org\\opencomb\\platform\\mvc\\controller\\DefaultController') ;
		return $aAccessRouter ;
	}
	
	public function createUISourceFileManager(Folder $aFolder)
	{
		$aSrcFileMgr = new SourceFileManager() ;
		UIFactory::singleton()->setSourceFileManager($aSrcFileMgr) ;
		MvcUIFactory::singleton()->setSourceFileManager($aSrcFileMgr) ;
		
		$aSrcFileMgr->addFolder( $aFolder->findFolder('/framework/template'), 'org.jecat.framework' ) ;
		$aSrcFileMgr->addFolder( $aFolder->findFolder('/platform/template') , 'org.opencomb' ) ;
		
		return $aSrcFileMgr ;
	}
}

?>