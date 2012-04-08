<?php
namespace org\opencomb\platform\service ;

use org\opencomb\platform\system\OcSession;
use org\jecat\framework\cache\FSCache;
use org\jecat\framework\setting\imp\FsSetting;
use org\jecat\framework\cache\Cache;
use org\jecat\framework\lang\oop\Package;
use org\jecat\framework\fs\Folder;
use org\opencomb\platform\ext\ExtensionLoader;
use org\jecat\framework\session\Session;
use org\jecat\framework\db\DB;
use org\jecat\framework\mvc\controller\Response;
use org\jecat\framework\mvc\controller\Request;
use org\jecat\framework\setting\Setting;
use org\jecat\framework\locale\LocaleManager;
use org\jecat\framework\bean\BeanFactory;
use org\jecat\framework\lang\Object;
use org\jecat\framework\system\Application;
use org\jecat\framework\resrc\HtmlResourcePool;
use org\jecat\framework\ui\xhtml\UIFactory;
use org\opencomb\platform\ui\SourceFileManager;
use org\jecat\framework\system\HttpAppFactory;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework as jc;
use org\opencomb\platform as oc;
use org\jecat\framework\mvc\view\UIFactory as MvcUIFactory;
use org\jecat\framework\ui\SourceFileManager as JcSourceFileManager;
use org\jecat\framework\system\AccessRouter as JcAccessRouter;

class ServiceFactory extends HttpAppFactory
{
	static public function singleton($bCreateNew=true,$createArgvs=null,$sClass=null)
	{
		return Object::singleton($bCreateNew,null,__CLASS__) ;
	}
	
	public function create(array & $arrServiceSetting)
	{
		// 检查服务配置
		$this->checkingServiceSetting($arrServiceSetting) ;
		
		// 创建服务
		$aService = new Service() ;
		$aOriApp = Application::switchSingleton($aService) ;
		
		
		// filesystem
		$aFolder = new Folder($arrServiceSetting['folder']) ;
		Folder::setSingleton($aFolder) ;
		
		// setting
		$aSetting = FsSetting::createFromPath($arrServiceSetting['folder_setting']) ;
		Setting::setSingleton($aSetting) ;

		// 初始化 cache
		$aCache = new FSCache($arrServiceSetting['folder_cache']) ;
		Cache::setSingleton($aCache) ;

		$aServiceSerializer = ServiceSerializer::singleton(true,$aService) ;
		
		// 从缓存中恢复 Service ---------------
		if( !$aServiceSerializer->restore() )
		{			
			// 重建 Service
			// --------------------------
			
			// 初始化 class loader
			ClassLoader::setSingleton($this->createClassLoader($arrServiceSetting)) ;
			
			// (request/respone 需要在ClassLoader之后)
			$this->initServiceRequestResponse($aService) ;
			
			// AccessRouter
			JcAccessRouter::setSingleton($this->createAccessRouter($aService)) ;
			
			// LocalManager
			LocaleManager::setSingleton($this->createLocaleManager($aService)) ;
				
			// 模板文件
			JcSourceFileManager::setSingleton($this->createUISourceFileManager($arrServiceSetting)) ;
			
			// 初始化系统无须store/restore的部分
			$this->initServiceUnrestorableSystem($aService,$aFolder,$aSetting,$arrServiceSetting) ;
			
			// BeanFactory 类别名
			BeanFactory::singleton()->registerBeanClass('org\\opencomb\\platform\\mvc\\model\\db\\orm\\Prototype','prototype') ;
			BeanFactory::singleton()->registerBeanClass('org\\opencomb\\platform\\mvc\\model\\db\\orm\\Association','association') ;
			
			// store system objects !
			$aServiceSerializer->addSystemSingletons() ;
			
			// 加载所有扩展
			ExtensionLoader::singleton()->loadAllExtensions($aService,$aService->extensions()) ;
			
			// 计算 UI template 的编译策略签名
			UIFactory::singleton()->calculateCompileStrategySignture() ;			
			
			// 激活所有扩展
			ExtensionLoader::singleton()->enableExtensions($aService,$aService->extensions()) ;
		}

		else 
		{
			// 初始化系统无须store/restore的部分
			$this->initServiceUnrestorableSystem($aService,$aFolder,$aSetting,$arrServiceSetting) ;
			
			// (request/respone 需要在ClassLoader之后)
			$this->initServiceRequestResponse($aService) ;
			
			// 激活所有扩展
			ExtensionLoader::singleton()->enableExtensions($aService,$aService->extensions()) ;
		} 
		
		// 启用class路径缓存
		ClassLoader::singleton()->setEnableClassCache( Setting::singleton()->item('/service/class','enableClassPathCache',true) ) ;
				
		if($aOriApp)
		{
			Application::switchSingleton($aOriApp) ;
		}
		
		return $aService ;
	}

	private function checkingServiceSetting(array & $arrServiceSetting)
	{
		if(empty($arrServiceSetting['folder_data']))
		{
			$arrServiceSetting['folder_data'] = $arrServiceSetting['folder'] . '/data' ;
		}
		if(empty($arrServiceSetting['folder_cache']))
		{
			$arrServiceSetting['folder_cache'] = $arrServiceSetting['folder'] . '/data/cache' ;
		}
		if(empty($arrServiceSetting['folder_compiled_class']))
		{
			$arrServiceSetting['folder_compiled_class'] = $arrServiceSetting['folder'] . '/data/compiled/class' ;
		}
		if(empty($arrServiceSetting['folder_compiled_template']))
		{
			$arrServiceSetting['folder_compiled_template'] = $arrServiceSetting['folder'] . '/data/compiled/template' ;
		}
	
		if(empty($arrServiceSetting['folder_setting']))
		{
			$arrServiceSetting['folder_setting'] = $arrServiceSetting['folder'] . '/setting' ;
		}
	
		if(empty($arrServiceSetting['folder_files']))
		{
			$arrServiceSetting['folder_files'] = $arrServiceSetting['folder'] . '/files' ;
		}
	}
	
	private function initServiceRequestResponse(Service $aService)
	{
		// Request
		Request::setSingleton( $this->createRequest($aService) ) ;
		
		// Response
		Response::setSingleton( $this->createResponse($aService) ) ;
	}
	private function initServiceUnrestorableSystem(Service $aService,Folder $aFolder,Setting $aSetting,array & $arrServiceSetting)
	{			
		// 数据库
		$sDBConfig = $aSetting->item('/service/db','config','alpha') ;
		$aDB = new DB(
				$aSetting->item('/service/db/'.$sDBConfig,'dsn')
				, $aSetting->item('/service/db/'.$sDBConfig,'username')
				, $aSetting->item('/service/db/'.$sDBConfig,'password')
				, $aSetting->item('/service/db/'.$sDBConfig,'options',array(\PDO::MYSQL_ATTR_INIT_COMMAND=>"SET NAMES 'utf8'"))
		) ;
		// 表名称前缀
		if( $sTablePrefix=$aSetting->item('/service/db/'.$sDBConfig,'table_prefix',null) )
		{
			$aDB->setTableNamePrefix($sTablePrefix) ;
		}
		DB::setSingleton($aDB) ;

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
		$aPublicFolder = new Folder(oc\PATH.'/public') ;
		$aPublicFolder->setHttpUrl('/platform/public') ;
		
		$aPublicFolders = $aService->publicFolders() ;
		$aPublicFolders->addFolder($aPublicFolder,'org.opencomb.platform') ;
		HtmlResourcePool::setSingleton( new HtmlResourcePool($aPublicFolders) ) ;
		
		// 高速缓存
		if( $arrHsCacheSetting=$aSetting->item('/service/cache','high-speed',null) )
		{
			//try{
				$aHighSpeedCache = call_user_func( array($arrHsCacheSetting['driver'],'createInstance'),$arrHsCacheSetting['parameters'] ) ;
				Cache::setHighSpeed($aHighSpeedCache) ;
			//} catch (\Exception $e)
			{}
		}
	}
		
	public function createClassLoader(array & $arrServiceSetting)
	{		
		// 重建对像
		$aClassLoader = parent::createClassLoader() ;
		
		// Service class
		$aClassLoader->addPackage( 'org\\opencomb\\platform', new Folder(\org\opencomb\platform\CLASSPATH) ) ;

		// 类编译包
		$aCompiledPackage = new Package('',Folder::createFolder($arrServiceSetting['folder_compiled_class'])) ;
		$aClassLoader->addPackage( $aCompiledPackage, null, Package::compiled ) ;
		
		return $aClassLoader ;
	}
	
	public function createAccessRouter(Service $aApp)
	{
		$aAccessRouter = parent::createAccessRouter($aApp) ;
		$aAccessRouter->setDefaultController('org\\opencomb\\platform\\mvc\\controller\\DefaultController') ;
		return $aAccessRouter ;
	}
	
	public function createUISourceFileManager(array & $arrServiceSetting)
	{
		$aSrcFileMgr = new SourceFileManager() ;
		$aSrcFileMgr->setCompiledFolderPath($arrServiceSetting['folder_compiled_template']) ;
		
		UIFactory::singleton()->setSourceFileManager($aSrcFileMgr) ;
		MvcUIFactory::singleton()->setSourceFileManager($aSrcFileMgr) ;
		
		$aSrcFileMgr->addFolder( new Folder(jc\PATH.'/template'), 'org.jecat.framework' ) ;
		$aSrcFileMgr->addFolder( new Folder(oc\PATH.'/template') , 'org.opencomb.platform' ) ;
		
		return $aSrcFileMgr ;
	}
}

