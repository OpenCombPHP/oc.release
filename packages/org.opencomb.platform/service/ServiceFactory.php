<?php
namespace org\opencomb\platform\service ;

use org\opencomb\platform\util\EventHandlers;
use org\jecat\framework\util\EventManager;
use org\jecat\framework\lang\Exception;
use org\opencomb\platform\system\OcSession;
use org\jecat\framework\cache\FSCache;
use org\jecat\framework\setting\imp\FsSetting;
use org\jecat\framework\setting\imp\ScalableSetting;
use org\jecat\framework\setting\imp\SaeMemcacheSetting;
use org\jecat\framework\cache\Cache;
use org\jecat\framework\lang\oop\Package;
use org\jecat\framework\fs\Folder;
use org\opencomb\platform\ext\ExtensionLoader;
use org\jecat\framework\session\Session;
use org\jecat\framework\db\DB;
use org\jecat\framework\mvc\controller\Response;
use org\jecat\framework\mvc\controller\Request;
use org\jecat\framework\setting\Setting;
use org\jecat\framework\locale\Locale;
use org\jecat\framework\lang\Object;
use org\jecat\framework\system\Application;
use org\jecat\framework\ui\xhtml\UIFactory;
use org\opencomb\platform\ui\SourceFileManager;
use org\jecat\framework\system\HttpAppFactory;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\resrc\ResourceManager;
use org\jecat\framework as jc;
use org\opencomb\platform as oc;
use org\jecat\framework\mvc\view\UIFactory as MvcUIFactory;
use org\jecat\framework\ui\SourceFileManager as JcSourceFileManager;
use org\jecat\framework\system\AccessRouter as JcAccessRouter;
use org\opencomb\platform\service\upgrader\PlatformDataUpgrader ;
use org\jecat\framework\message\MessageQueue;

class ServiceFactory extends HttpAppFactory
{
	static public function singleton($bCreateNew=true,$createArgvs=null,$sClass=null)
	{
		return Object::singleton($bCreateNew,null,__CLASS__) ;
	}
	
	static public function setSingleton(self $aInstance=null)
	{
		parent::setSingleton($aInstance) ;
	}
	
	public function __construct(array &$arrServiceSettings){
		// 检查服务配置
		$this->checkingServiceSetting( $arrServiceSettings );
		
		$this->arrServiceSettings = &$arrServiceSettings ;
	}
	
	public function startBaseSystem(){
		// filesystem
		$aFolder = new Folder($this->arrServiceSettings['folder_path']) ;
		Folder::setSingleton($aFolder) ;
		
		// setting
		$aSetting = $this->createServiceSetting(
			$this->arrServiceSettings['serviceSetting']
		);
		Setting::setSingleton($aSetting) ;
	}
	
	public function create()
	{
		$arrServiceSetting = &$this->arrServiceSettings ;
		// 创建服务
		$aService = new Service() ;
		$aService->setServiceSetting($arrServiceSetting) ;
		$aOriApp = Application::switchSingleton($aService) ;
		
		$aFolder = Folder::singleton() ;
		$aSetting = Setting::singleton() ;
		
		// 初始化 cache
		$aCache = $this->createCache($aService,$arrServiceSetting['folder_cache']) ;
		
		// 从缓存中恢复 Service ---------------
		$aServiceSerializer=$this->createServiceSerializer($aService) ;
		if( !$aSetting->value('/service/serialize',false) or ($aServiceSerializer and !$aServiceSerializer->restore($aSetting)) )
		{
			// 重建 Service
			// --------------------------
			
			// 初始化 class loader
			ClassLoader::setSingleton($this->createClassLoader($arrServiceSetting)) ;
			
			// (request/respone 需要在ClassLoader之后)
			$this->initServiceRequestResponse($aService) ;
			
			// AccessRouter
			JcAccessRouter::setSingleton($this->createAccessRouter($aService)) ;
			
			// Locale
			Locale::createSessionLocale(
				$aSetting->value('/service/locale/language','zh'), $aSetting->value('/service/locale/country','CN'), true
			) ;
			
			// 模板文件
			JcSourceFileManager::setSingleton($this->createUISourceFileManager($arrServiceSetting)) ;
			
			// 初始化系统无须store/restore的部分
			$this->initServiceUnrestorableSystem($aService,$aFolder,$aSetting,$arrServiceSetting) ;
			
			// store system objects !
			if(isset($aServiceSerializer))
			{
				$aServiceSerializer->addSystemSingletons() ;
			}
			
			// 注册事件
			EventHandlers::registerEventHandlers(EventManager::singleton()) ;
			
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
		ClassLoader::singleton()->setEnableClassCache( Setting::singleton()->value('/service/class/enableClassPathCache',true) ) ;
		
		if($aOriApp)
		{
			Application::switchSingleton($aOriApp) ;
		}
		
		$aMesgQ = MessageQueue::flyWeight('dataUpgrade');
		$aMesgQ->display();
		$aService->setEnableDataUpgrader(false);
		
		return $aService ;
	}

	private function checkingServiceSetting(array & $arrServiceSetting)
	{
		if(empty($arrServiceSetting['folder_data']))
		{
			$arrServiceSetting['folder_data'] = $arrServiceSetting['folder_path'] . '/data' ;
		}
		if(empty($arrServiceSetting['folder_cache']))
		{
			$arrServiceSetting['folder_cache'] = $arrServiceSetting['folder_path'] . '/data/cache' ;
		}
		if(empty($arrServiceSetting['folder_compiled_class']))
		{
			$arrServiceSetting['folder_compiled_class'] = $arrServiceSetting['folder_path'] . '/data/compiled/class' ;
		}
		if(empty($arrServiceSetting['folder_compiled_template']))
		{
			$arrServiceSetting['folder_compiled_template'] = $arrServiceSetting['folder_path'] . '/data/compiled/template' ;
		}
		
		if(empty($arrServiceSetting['folder_setting']))
		{
			$arrServiceSetting['folder_setting'] = $arrServiceSetting['folder_path'] . '/setting' ;
		}
		
		if(empty($arrServiceSetting['folder_files']))
		{
			$arrServiceSetting['folder_files'] = $arrServiceSetting['folder_path'] . '/files' ;
		}
		if(empty($arrServiceSetting['folder_files_url']))
		{
			$arrServiceSetting['folder_files_url'] = (empty($_SERVER['HTTPS'])?'http://':'https://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) ;
			if( substr($arrServiceSetting['folder_files_url'],-1)!=='/' )
			{
				$arrServiceSetting['folder_files_url'].= '/' ;
			}
			$arrServiceSetting['folder_files_url'].= 'services/' . $arrServiceSetting['folder_name'] . '/files' ;
		}
		if(empty($arrServiceSetting['serviceSetting'])){
			$arrServiceSetting['serviceSetting'] = array(
				'type' => self::FS_SETTING,
			);
		}
	}
	
	const FS_SETTING = 'FS_SETTING';
	const SCALABLE_SETTING = 'SCALABLE_SETTING';
	const SAE_MEMCACHE_SETTING = 'SAE_MEMCACHE_SETTING';
	public function createServiceSetting(array $arrSetting){
		switch($arrSetting['type']){
		case self::FS_SETTING :
			return FsSetting::createFromPath($this->arrServiceSettings['folder_setting']);
			break;
		case self::SCALABLE_SETTING :
			return new ScalableSetting(
				$this->createServiceSetting(
					$arrSetting['innerSetting']
				)
			);
			break;
		case self::SAE_MEMCACHE_SETTING :
			return new SaeMemcacheSetting();
			break;
		default:
			throw new Exception(
				"无效的setting配置：\n %s",
				var_export($arrSetting,true)
			);
		}
	}
	
	protected function createServiceSerializer(Service $aService)
	{
		return ServiceSerializer::singleton(true,$aService) ;
	}
	
	protected function createCache(Service $aService,$sSerivceCacheFolder)
	{
		// (debug模式下不使用缓存)
		if( !$aService->isDebugging() )
		{
			$aCache = new FSCache($sSerivceCacheFolder) ;
			Cache::setSingleton($aCache) ;
			
			return $aCache ;
		}
		else 
		{
			return null ;
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
		$sDBConfig = $aSetting->value('/service/db/config','alpha') ;
		if( !$sDsn=$aSetting->value('/service/db/'.$sDBConfig.'/dsn')
				or !$sUsername=$aSetting->value('/service/db/'.$sDBConfig.'/username')
		)
		{
			throw new Exception("数据库配置无效(config: %s;dsn: %s; user: %s; passwd: %s)",array(
			  $sDBConfig, @$sDsn, @$sUsername, (@$sPassword? '[used]': '[empty]')
			)) ;
		}
		$sPassword=$aSetting->value('/service/db/'.$sDBConfig.'/password') ;
		$sOptions = $aSetting->value('/service/db/'.$sDBConfig.'/options',array(\PDO::MYSQL_ATTR_INIT_COMMAND=>"SET NAMES 'utf8'")) ;
		
		$aDB = new DB( $sDsn, $sUsername, $sPassword, $sOptions ) ;
		
		// 表名称前缀
		if( $sTablePrefix=$aSetting->value('/service/db/'.$sDBConfig.'/table_prefix',null) )
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
		
		
		// 高速缓存
		if( !$aService->isDebugging() and $arrHsCacheSetting=$aSetting->value('/service/cache/high-speed',null) )
		{
			//try{
				$aHighSpeedCache = call_user_func( array($arrHsCacheSetting['driver'],'createInstance'),$arrHsCacheSetting['parameters'] ) ;
				Cache::setHighSpeed($aHighSpeedCache) ;
			//} catch (\Exception $e)
			{}
		}
		
		// html resource folders
		$aPublicFolders = new ResourceManager() ;
		$aPublicFolders->addFolder(new Folder($arrServiceSetting['framework_folder'].'/public',0,$arrServiceSetting['framework_url']."/public"),'org.jecat.framework') ;
		$aPublicFolders->addFolder(new Folder($arrServiceSetting['platform_folder'].'/public',0,$arrServiceSetting['platform_url']."/public"),'org.opencomb.platform') ;
		$aService->setPublicFolders($aPublicFolders) ;
		
		
		$bEnableDataUpgrader = $aService->isEnableDataUpgrader() ;
		$bDebug = $aService->isDebugging();
		
		if( $bEnableDataUpgrader or $bDebug ){
			// 检查 service 升级
			$aDataUpgrader = PlatformDataUpgrader::singleton() ; 
			$aMessageQueue = MessageQueue::flyWeight('dataUpgrade');
			if(TRUE === $aDataUpgrader->process($aMessageQueue)){
				// $aDataUpgrader->relocation();
			}
		}
	}
	
	public function createClassLoader(array & $arrServiceSetting)
	{
		// 重建对像
		$aClassLoader = parent::createClassLoader() ;
		
		// Service packages
		$aClassLoader->addPackage( 'org\\opencomb\\platform', new Folder(\org\opencomb\platform\PATH.'/packages/org.opencomb.platform') ) ;
		$aClassLoader->addPackage( 'net\\phpconcept\\pclzip', new Folder(\org\opencomb\platform\PATH.'/packages/net.phpconcept.pclzip') ) ;
		
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
	
	private $arrServiceSettings = array() ;
}
