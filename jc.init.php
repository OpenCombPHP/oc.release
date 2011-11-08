<?php 
namespace oc ;

use oc\Platform;
use oc\system\PlatformFactory;
use jc\session\OriginalSession;
use jc\session\Session;
use jc\db\DB;
use jc\db\driver\PDODriver;


ini_set('display_errors',1) ;
error_reporting(E_ALL^E_STRICT) ;


require_once __DIR__."/framework/inc.entrance.php" ;
require_once __DIR__."/framework/src/lib.php/system/HttpAppFactory.php" ;
require_once __DIR__."/platform/class/Platform.php" ;
require_once __DIR__."/platform/class/system/PlatformFactory.php" ;


$aPlatform = PlatformFactory::singleton()->create(__DIR__) ;


// 数据库
DB::singleton()->setDriver( new PDODriver("mysql:host=192.168.1.1;dbname=oc",'root','1') ) ;

// 会话
Session::setSingleton( new OriginalSession() ) ;


return $aPlatform ;
?>