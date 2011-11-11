<?php
namespace oc ;

// 初始化 jcat 框架
use jc\fs\imp\LocalFileSystem;
use jc\fs\File;
use oc\mvc\model\db\orm\PAMap;
use jc\mvc\model\db\orm\PrototypeAssociationMap;
use oc\ext\ExtensionMetainfo;

$t = microtime(1) ;


// 简单配置启动 OC platform,以及扩展, 以后完善
$aPlatform = require 'jc.init.php' ;

// 启用class编译
$aPlatform->classLoader()->enableClassCompile() ;

/*
$aFile = new File(__DIR__.'/extensions/blog/class/Blog.php') ;
$aPlatform->classLoader()->compiler()->compile($aFile->openReader(),$aPlatform->response()->printer()) ;
exit() ;
*/


$aPlatform->load() ;

// 根据路由设置创建控制器 并 执行
$aController = $aPlatform->accessRouter()->createRequestController($aPlatform->request()) ;
if($aController)
{
	$aController->mainRun() ;
}
else 
{
	header("HTTP/1.0 404 Not Found");
	echo "<h1>Page Not Found</h1>" ;
}

//echo microtime(1) - $t, "<br />\r\n" ;
//echo $aPlatform->classLoader()->totalLoadTime() ;
?>
