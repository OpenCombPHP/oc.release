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

// 初始化 
//PrototypeAssociationMap::setSingleton(new PAMap()) ;

// sns
//$aPlatform->loadExtension(new ExtensionMetainfo('sns','oc\ext\sns\Sns')) ;

// coreuser
// $aPlatform->loadExtension(new ExtensionMetainfo('coreuser','oc\ext\coreuser\CoreUser')) ;

// blog
//$aPlatform->loadExtension(new ExtensionMetainfo('blog','oc\ext\blog\Blog')) ;

// groups
//$aPlatform->loadExtension(new ExtensionMetainfo('groups','oc\ext\groups\Groups')) ;

// microblog
//$aPlatform->loadExtension(new ExtensionMetainfo('microblog','oc\ext\microblog\MicroBlog')) ;

// developtoolbox
//$aPlatform->loadExtension(new ExtensionMetainfo('developtoolbox','oc\ext\developtoolbox\DevelopToolbox')) ;

// instantmessaging
//$aPlatform->loadExtension(new ExtensionMetainfo('instantmessaging','oc\ext\instantmessaging\InstantMessaging')) ;

// album
//$aPlatform->loadExtension(new ExtensionMetainfo('album','oc\ext\album\Album')) ;

// comment
//$aPlatform->loadExtension(new ExtensionMetainfo('comment','oc\ext\comment\Comment')) ;

// Real Knowledge Platform
//$aPlatform->loadExtension(new ExtensionMetainfo('realknowledgeplatform','oc\ext\realknowledgeplatform\RealKnowledgePlatform')) ;

// tester
//$aPlatform->loadExtension(new ExtensionMetainfo('pearcommon','oc\ext\pearcommon\PearCommon')) ;
//$aPlatform->loadExtension(new ExtensionMetainfo('pearphp','oc\ext\pearphp\PearPHP')) ;
//$aPlatform->loadExtension(new ExtensionMetainfo('pearphpunit','oc\ext\pearphpunit\PearPHPUnit')) ;
//$aPlatform->loadExtension(new ExtensionMetainfo('tester','oc\ext\tester\Tester')) ;

// 启用class编译
$aPlatform->classLoader()->enableClassCompile() ;

// 访问入口
if( $aFs = $aPlatform->fileSystem()->findFolder('/') )
{
	$aFs->setHttpUrl(
		dirname($aPlatform->request()->url())
	) ;
}

/*
$aFile = new File(__DIR__.'/extensions/blog/class/Blog.php') ;
$aPlatform->classLoader()->compiler()->compile($aFile->openReader(),$aPlatform->response()->printer()) ;
exit() ;
*/


$aPlatform->init() ;

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
