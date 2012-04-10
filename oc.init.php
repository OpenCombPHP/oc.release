<?php 
namespace org\opencomb\platform ;

ini_set('display_errors',1) ;
error_reporting(E_ALL^E_STRICT) ;

// 定义系统常量
define('org\\opencomb\\platform\\ROOT',__DIR__) ;
define('org\\opencomb\\platform\\PATH',__DIR__.'/platform') ;
define('org\\opencomb\\platform\\CLASSPATH',__DIR__.'/platform/class') ;
define('org\\opencomb\\platform\\SERVICE_ROOT',__DIR__.'/services') ;

// 加载 jecat framework 
require_once __DIR__."/framework/inc.entrance.php" ;

// load jecat core class
require_once \org\jecat\framework\CLASSPATH."/system/HttpAppFactory.php" ;
require_once \org\jecat\framework\CLASSPATH."/setting/ISetting.php" ;
require_once \org\jecat\framework\CLASSPATH."/setting/Setting.php" ;
require_once \org\jecat\framework\CLASSPATH."/setting/IKey.php" ;
require_once \org\jecat\framework\CLASSPATH."/setting/Key.php" ;
require_once \org\jecat\framework\CLASSPATH."/setting/imp/FsSetting.php" ;
require_once \org\jecat\framework\CLASSPATH."/setting/imp/FsKey.php" ;
require_once \org\jecat\framework\CLASSPATH.'/cache/Cache.php' ;
require_once \org\jecat\framework\CLASSPATH.'/cache/FSCache.php' ;
require_once \org\jecat\framework\CLASSPATH.'/cache/EmptyCache.php' ;

// load opencomb core class
require_once CLASSPATH."/Platform.php" ;
require_once CLASSPATH."/service/Service.php" ;
require_once CLASSPATH."/service/ServiceFactory.php" ;
require_once CLASSPATH."/service/ServiceSerializer.php" ;


// 初始化 platform
$aPlatform = Platform::singleton() ;


// 创建 service
$aService = $aPlatform->createService($_SERVER['HTTP_HOST']) ;
// Service::setInstance($aService) ;


// 检查 service 状态 (是否关闭)
/*if( is_file(__DIR__.'/lock.shutdown.html') )
{
	// 检查”后门“密钥，方便管理员进入
	if( empty($_REQUEST['shutdown_backdoor_secret_key']) or !is_file(__DIR__.'/lock.shutdown.backdoor.php') or include(__DIR__.'/lock.shutdown.backdoor.php')!=$_REQUEST['shutdown_backdoor_secret_key'] )
	{
		// ”后门密钥“检查失败，关闭系统
		include __DIR__.'/lock.shutdown.html' ;
		exit() ;
	}
}*/


// 检查 service 升级
/*$aDataUpgrader = PlatformDataUpgrader::singleton() ; 
if(TRUE === $aDataUpgrader->process()){
	$aDataUpgrader->relocation();
	exit();
}*/

// 
return $aService ;
