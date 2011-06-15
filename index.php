<?php
namespace oc ;

// 初始化 jcat 框架
use jc\db\DB;
use jc\db\PDODriver;
use jc\ui\xhtml\Factory as UIFactory ;
use jc\system\Application;
use jc\test\integration\HtmlResourcePoolFactory;

$aApp = include __DIR__."/framework/inc.entrance.php" ;


// 简单配置启动 OC platform,以及扩展, 以后完善
require 'config.php' ;
$aUISrcMgr = UIFactory::singleton()->sourceFileManager() ;
$aJsFileMgr = HtmlResourcePoolFactory::singleton()->javaScriptFileManager() ;
$aCssFileMgr = HtmlResourcePoolFactory::singleton()->cssFileManager() ;

// oc platform
$aApp->classLoader()->addPackage(__DIR__.'/classes','oc') ;

// core.user
$aApp->classLoader()->addPackage(__DIR__.'/extensions/coreuser/classes','oc\ext\coreuser') ;
$aUISrcMgr->addFolder(__DIR__.'/extensions/coreuser/ui/templates') ;
$aJsFileMgr->addFolder(__DIR__.'/extensions/coreuser/ui/js') ;
$aCssFileMgr->addFolder(__DIR__.'/extensions/coreuser/ui/css') ;

?>