<?php
namespace oc ;

// 初始化 jcat 框架
use oc\mvc\model\db\orm\PAMap;
use jc\mvc\model\db\orm\PrototypeAssociationMap;
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
PrototypeAssociationMap::setSingleton(new PAMap()) ;

// sns
$aPlatform->loadExtension(new ExtensionMetainfo('sns','oc\ext\sns\Sns')) ;

// coreuser
$aPlatform->loadExtension(new ExtensionMetainfo('coreuser','oc\ext\coreuser\CoreUser')) ;

// blog
$aPlatform->loadExtension(new ExtensionMetainfo('blog','oc\ext\blog\Blog')) ;

// groups
$aPlatform->loadExtension(new ExtensionMetainfo('groups','oc\ext\groups\Groups')) ;

// microblog
$aPlatform->loadExtension(new ExtensionMetainfo('microblog','oc\ext\microblog\MicroBlog')) ;

// developtoolbox
$aPlatform->loadExtension(new ExtensionMetainfo('developtoolbox','oc\ext\developtoolbox\DevelopToolbox')) ;

// instantmessaging
$aPlatform->loadExtension(new ExtensionMetainfo('instantmessaging','oc\ext\instantmessaging\InstantMessaging')) ;

// electronicnewspaper
$aPlatform->loadExtension(new ExtensionMetainfo('electronicnewspaper','oc\ext\electronicnewspaper\ElectronicNewspaper')) ;

// tourdmedm
//$aPlatform->loadExtension(new ExtensionMetainfo('tourdmedm','oc\ext\tourdmedm\Tourdmedm')) ;




// 根据路由设置创建控制器 并 执行
$aController = $aPlatform->accessRouter()->createRequestController($aPlatform->request()) ;
if($aController)
{
	$aController->mainRun() ;
}
else 
{
	header("HTTP/1.0 404 Not Found");
}

?>