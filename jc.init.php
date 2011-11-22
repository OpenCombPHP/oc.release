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
$aSetting = $aPlatform->setting() ;

// 数据库
$sDBConfig = $aSetting->item('/platform/db','config','alpha') ;
$aDBDriver = new PDODriver(
		$aSetting->item('/platform/db/'.$sDBConfig,'dsn')
		, $aSetting->item('/platform/db/'.$sDBConfig,'username')
		, $aSetting->item('/platform/db/'.$sDBConfig,'password')
		, $aSetting->item('/platform/db/'.$sDBConfig,'options',array(\PDO::MYSQL_ATTR_INIT_COMMAND=>"SET NAMES 'utf8'"))
) ;
DB::singleton()->setDriver($aDBDriver) ;

// 会话
Session::setSingleton( new OriginalSession() ) ;


return $aPlatform ;
?>
