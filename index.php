<?php
namespace oc ;

// 初始化 jcat 框架
use oc\ext\ExtensionMetainfo;
use jc\mvc\view\htmlresrc\HtmlResourcePoolFactory ;


ini_set('display_errors', 1);

require_once __DIR__."/framework/inc.entrance.php" ;
require_once __DIR__."/platform/class/Platform.php" ;

$aPlatform = new Platform(__DIR__) ;

// 简单配置启动 OC platform,以及扩展, 以后完善
require 'config.php' ;


// coreuser
$aExtMeta = new ExtensionMetainfo('coreuser','oc\ext\coreuser\CoreUser') ;
$aExtMeta->load($aPlatform) ;

// developtoolbox
$aExtMeta = new ExtensionMetainfo('developtoolbox','oc\ext\developtoolbox\DevelopToolbox') ;
$aExtMeta->load($aPlatform) ;



// 根据路由设置创建控制器 并 执行
$aController = $aPlatform->accessRouter()->createRequestController($aPlatform->request()) ;
if($aController)
{
	$aController->mainRun($aPlatform->request()) ;
}
else 
{
	header("HTTP/1.0 404 Not Found");
}

?>