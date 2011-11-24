<?php
namespace org\opencomb ;

// 初始化 jcat 框架
use org\jecat\framework\system\Request;
use org\jecat\framework\system\AccessRouter;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\fs\imp\LocalFileSystem;
use org\jecat\framework\fs\File;
use org\opencomb\mvc\model\db\orm\PAMap;
use org\jecat\framework\mvc\model\db\orm\PrototypeAssociationMap;
use org\opencomb\ext\ExtensionMetainfo;

$t = microtime(1) ;


// 简单配置启动 OC platform,以及扩展, 以后完善
$aPlatform = require 'jc.init.php' ;

// 启用class编译
ClassLoader::singleton()->enableClassCompile() ;

/*
$aFile = new File(__DIR__.'/extensions/blog/class/Blog.php') ;
ClassLoader::singleton()->compiler()->compile($aFile->openReader(),$aPlatform->response()->printer()) ;
exit() ;
*/


$aPlatform->load() ;

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

//echo $aPlatform->signature() ;
//echo microtime(1) - $t, "<br />\r\n" ;
//echo ClassLoader::singleton()->totalLoadTime() ;
?>
