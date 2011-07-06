<?php
namespace oc ;

// 初始化 jcat 框架
use oc\mvc\model\db\orm\MAMap;
use jc\mvc\model\db\orm\ModelAssociationMap;
use oc\ext\ExtensionMetainfo;
use jc\resrc\htmlresrc\HtmlResourcePoolFactory ;


ini_set('display_errors', 1);
//error_reporting(E_ALL);

require_once __DIR__."/framework/inc.entrance.php" ;
require_once __DIR__."/framework/src/lib.php/system/HttpAppFactory.php" ;
require_once __DIR__."/platform/class/Platform.php" ;
require_once __DIR__."/platform/class/system/PlatformFactory.php" ;

$aPlatform = new Platform(__DIR__) ;

// 简单配置启动 OC platform,以及扩展, 以后完善
require 'config.php' ;

// 初始化 
ModelAssociationMap::setSingleton(new MAMap()) ;

// coreuser
$aExtMeta = new ExtensionMetainfo('coreuser','oc\ext\coreuser\CoreUser') ;
$aExtMeta->load($aPlatform) ;

// blog
$aExtMeta = new ExtensionMetainfo('blog','oc\ext\blog\Blog') ;
$aExtMeta->load($aPlatform) ;

// groups
$aExtMeta = new ExtensionMetainfo('groups','oc\ext\groups\Groups') ;
$aExtMeta->load($aPlatform) ;

// microblog
$aExtMeta = new ExtensionMetainfo('microblog','oc\ext\microblog\MicroBlog') ;
$aExtMeta->load($aPlatform) ;

// developtoolbox
$aExtMeta = new ExtensionMetainfo('developtoolbox','oc\ext\developtoolbox\DevelopToolbox') ;
$aExtMeta->load($aPlatform) ;

// instantmessaging
$aExtMeta = new ExtensionMetainfo('instantmessaging','oc\ext\instantmessaging\InstantMessaging') ;
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