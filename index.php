<?php
namespace org\opencomb\platform ;

use org\jecat\framework\db\DB;
use org\jecat\framework\mvc\controller\Request;
use org\jecat\framework\system\AccessRouter;
use org\opencomb\platform\system\upgrader\PlatformDataUpgrader ;


// 初始化 jcat 框架
$aPlatform = require 'oc.init.php' ;

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

if( empty($_REQUEST['rspn']) )
{
	// echo $fTime2 - $fTime ;
}
/*
$aExecuteTimeWatcher->finish('/system/total') ;

if( empty($_REQUEST['rspn']) )
{
	$aExecuteTimeWatcher->printLogs() ;
}*/
