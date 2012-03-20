<?php
namespace org\opencomb\platform ;

$fStartTime = microtime(true) ;

use org\opencomb\platform\debug\ExecuteTimeWatcher;
use org\jecat\framework\db\DB;
use org\jecat\framework\mvc\controller\Request;
use org\jecat\framework\system\AccessRouter;
use org\opencomb\platform\system\upgrader\PlatformDataUpgrader ;

// 检查系统关闭锁
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
$aPlatform = require 'oc.init.php' ;

$aExecuteTimeWatcher = ExecuteTimeWatcher::singleton() ;
$aExecuteTimeWatcher->start('/system/total',$fStartTime) ;
$aExecuteTimeWatcher->start('/system/init',$fStartTime) ;
$aExecuteTimeWatcher->finish('/system/init') ;

$aDataUpgrader = PlatformDataUpgrader::singleton() ; 
if(TRUE === $aDataUpgrader->process()){
	$aDataUpgrader->relocation();
	exit();
}


// 根据路由设置创建控制器 并 执行
$aController = AccessRouter::singleton()->createRequestController(Request::singleton()) ;
if($aController)
{
	$t = microtime(1) ;
	$aController->mainRun() ;
	$fControllerExecuteTime = microtime(1) - $t ;
}
else 
{
	header("HTTP/1.0 404 Not Found");
	echo "<h1>Page Not Found</h1>" ;
}

$aExecuteTimeWatcher->finish('/system/total') ;

if( empty($_REQUEST['rspn']) )
{
	$aExecuteTimeWatcher->printLogs() ;
}
