<?php
namespace org\opencomb\platform\service ;

use org\jecat\framework\fs\Folder;
use org\jecat\framework\lang\Object;
use org\jecat\framework\setting\Setting;

/**
 * @wiki /蜂巢/关闭系统
 *
 * org\opencomb\platform\system\PlatformShutdowner::shutdown() 方法关闭系统，并返回后门密钥。
 * org\opencomb\platform\system\PlatformShutdowner::restore() 方法恢复系统访问。
 * 
 * =“后门”密钥=
 * 系统在关闭状态时，可以通过 Get/Post/Cookie 等方式，提供”后门“密钥来访问系统。
 */
class ServiceShutdowner extends Object
{
	public function shutdown($sMessage="系统正在离线升级中……")
	{
		$sContents = self::$sTemplate ;
		$sContents = str_replace('%title%', sprintf(Service::singleton()->setting()->item('/platform','systemname'),"系统关闭"), $sContents) ;
		$sContents = str_replace('%contents%', $sMessage, $sContents) ;
		
		Folder::singleton()->findFile('/lock.shutdown.html',Folder::FIND_AUTO_CREATE)->openWriter()->write($sContents) ;
		
		return $this->backdoorSecretKey(true) ;
	}
	
	public function backdoorSecretKey($bAutoCreate=false)
	{
		if( $aSkFile=Folder::singleton()->findFile('/lock.shutdown.backdoor.php') )
		{
			return $aSkFile->includeFile(false,false) ;
		}
		
		if($bAutoCreate)
		{
			$sBackDoorSecretKey = md5(microtime()) ;
			
			Folder::singleton()->findFile('/lock.shutdown.backdoor.php',Folder::FIND_AUTO_CREATE)->openWriter()->write("<?php
// 系统关闭的后门密钥，用于管理员进入系统
return \$sBackDoorSecretKey = '{$sBackDoorSecretKey}' ;") ;
			
			return $sBackDoorSecretKey ;
		}
		
		return null ;
		// is_file(__DIR__.'') or include(__DIR__.'/lock.shutdown.backdoor.php')!=$_REQUEST['shutdown_backdoor_secret_key'] ;
	}
	
	public function restore()
	{
		Folder::singleton()->deleteChild('/lock.shutdown.html') ;
		Folder::singleton()->deleteChild('/lock.shutdown.backdoor.php') ;
	}
	
	static private $sTemplate = <<<TEMPLATE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
		<meta name="keywords" content="%keywords%" />
		<meta name="description" content="%description%" />
		<title>%title%</title>
	</head>
	
	<body>
		%contents%
	</body>
</html>
	
TEMPLATE
	;
}
