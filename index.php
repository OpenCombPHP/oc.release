<?php
namespace org\opencomb\platform ;

// 检查系统关闭锁
use org\jecat\framework\db\DB;

if( is_file(__DIR__.'/lock.shutdown.html') )
{
	// 检查”后门“密钥，方便管理员进入
	if( empty($_REQUEST['shutdown_backdoor_secret_key']) or !is_file(__DIR__.'/lock.shutdown.backdoor.php') or include(__DIR__.'/lock.shutdown.backdoor.php')!=$_REQUEST['shutdown_backdoor_secret_key'] )
	{
		// ”后门密钥“检查失败，关闭系统
		include __DIR__.'/lock.shutdown.html' ;
		exit() ;
	}
}

// 初始化 jcat 框架
use org\jecat\framework\mvc\controller\Request;
use org\jecat\framework\system\AccessRouter;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\fs\imp\LocalFileSystem;
use org\jecat\framework\fs\File;
use org\jecat\framework\mvc\model\db\orm\PrototypeAssociationMap;
use org\opencomb\platform\mvc\model\db\orm\PAMap;
use org\opencomb\platform\ext\ExtensionMetainfo;
use org\opencomb\platform\system\upgrader\PlatformDataUpgrader ;




// 简单配置启动 OC platform,以及扩展, 以后完善
$t = microtime(1) ;
$aPlatform = require 'jc.init.php' ;
$fPlatformInitTime = microtime(1) - $t ;

$aDataUpgrader = PlatformDataUpgrader::singleton() ; 
if(TRUE === $aDataUpgrader->process()){
	$aDataUpgrader->relocation();
	exit();
}


// 根据路由设置创建控制器 并 执行
$t = microtime(1) ;
$aController = AccessRouter::singleton()->createRequestController(Request::singleton()) ;
if($aController)
{
	$aController->mainRun() ;
}
else 
{
	header("HTTP/1.0 404 Not Found");
	echo "<h1>Page Not Found</h1>" ;
}
$fExecuteTime = microtime(1) - $t ;

if(empty($_REQUEST['rspn'])){
	//echo $aPlatform->signature() ;
	echo 'total: ', $aPlatform->uptime(true),'<br />' ;
	echo 'platform init: ', $fPlatformInitTime,'<br />' ;
	echo 'controller execute: ', $fExecuteTime,'<br />' ;
	echo 'class load: ', ClassLoader::singleton()->totalLoadTime() ;
}
