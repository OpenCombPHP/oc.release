<?php
namespace org\opencomb\platform ;

$arrInbuildClass = get_declared_classes() ;

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
use org\jecat\framework\mvc\controller\Request;
use org\jecat\framework\system\AccessRouter;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\fs\imp\LocalFileSystem;
use org\jecat\framework\fs\File;
use org\jecat\framework\mvc\model\db\orm\PrototypeAssociationMap;
use org\opencomb\platform\mvc\model\db\orm\PAMap;
use org\opencomb\platform\ext\ExtensionMetainfo;


$t = microtime(1) ;


// 简单配置启动 OC platform,以及扩展, 以后完善
$aPlatform = require 'jc.init.php' ;

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


if(empty($_REQUEST['rspn'])){
	//echo $aPlatform->signature() ;
	echo $aPlatform->uptime(true),'<br />' ;
	echo ClassLoader::singleton()->totalLoadTime() ;
}
