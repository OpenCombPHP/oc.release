<?php
namespace org\opencomb\platform ;

use org\jecat\framework\db\DB;
use org\jecat\framework\mvc\controller\Request;
use org\jecat\framework\system\AccessRouter;
use org\opencomb\platform\system\upgrader\PlatformDataUpgrader ;

$fTimeStart = microtime(true) ;

// 初始化 jcat 框架
$aService = require 'oc.init.php' ;
$fInitFinish = microtime(true) ;


// 根据路由设置创建控制器 并 执行
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

if( empty($_REQUEST['rspn']) and $aService->isDebugging() )
{
	echo $fInitFinish - $fTimeStart, '<br />', microtime(true) - $fTimeStart ;
}
/*
$aExecuteTimeWatcher->finish('/system/total') ;

if( empty($_REQUEST['rspn']) )
{
	$aExecuteTimeWatcher->printLogs() ;
}*/
