<?php 
namespace org\opencomb\platform ;

use org\jecat\framework\setting\Setting;
use org\jecat\framework\fs\imp\LocalFileSystem;
use org\opencomb\platform\Platform;
use org\opencomb\platform\system\PlatformFactory;


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
require_once __DIR__."/platform/class/system/PlatformSerializer.php" ;


$aPlatform = PlatformFactory::singleton()->create(__DIR__) ;
$aSetting = Setting::singleton() ;


return $aPlatform ;

