<?php 
namespace org\opencomb ;

use org\jecat\framework\setting\Setting;
use org\jecat\framework\fs\imp\LocalFileSystem;
use org\opencomb\Platform;
use org\opencomb\system\PlatformFactory;
use org\jecat\framework\session\OriginalSession;
use org\jecat\framework\session\Session;
use org\jecat\framework\db\DB;
use org\jecat\framework\db\driver\PDODriver;


ini_set('display_errors',1) ;
error_reporting(E_ALL^E_STRICT) ;


require_once __DIR__."/framework/inc.entrance.php" ;
require_once __DIR__."/framework/class/system/HttpAppFactory.php" ;
require_once __DIR__."/framework/class/fs/imp/LocalFileSystem.php" ;
require_once __DIR__."/framework/class/cache/ICache.php" ;
require_once __DIR__."/framework/class/cache/FSCache.php" ;
require_once __DIR__."/framework/class/setting/ISetting.php" ;
require_once __DIR__."/framework/class/setting/Setting.php" ;
require_once __DIR__."/framework/class/setting/IKey.php" ;
require_once __DIR__."/framework/class/setting/Key.php" ;
require_once __DIR__."/framework/class/setting/imp/FsSetting.php" ;
require_once __DIR__."/framework/class/setting/imp/FsKey.php" ;
require_once __DIR__."/platform/class/Platform.php" ;
require_once __DIR__."/platform/class/system/PlatformFactory.php" ;


$aPlatform = PlatformFactory::singleton()->create(__DIR__) ;
$aSetting = Setting::singleton() ;

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

