<?php
namespace oc ;

use jc\system\Application;
use jc\system\AppFactory;
use jc\ui\xhtml\Factory as UIFactory;
use jc\mvc\view\htmlresrc\HtmlResourcePoolFactory;

class Platform extends Application
{
	public function __construct($sAppDir)
	{
		parent::__construct() ;
		
		if( !Application::singleton(false) )
		{
			Application::setSingleton($this) ;
		}
		
		$aAppFactory = AppFactory::createFactory() ;
		$aAppFactory->build($this) ;		
		
		// class
		$this->classLoader()->addPackage(__DIR__,'oc') ;
		
		// app dir
		$this->setApplicationDir($sAppDir) ;
		
		UIFactory::singleton()->sourceFileManager()->addFolder($sAppDir.'/platform/ui/template/') ;
		HtmlResourcePoolFactory::singleton()->javaScriptFileManager()->addFolder($sAppDir.'/platform/ui/js/') ;
		HtmlResourcePoolFactory::singleton()->cssFileManager()->addFolder($sAppDir.'/platform/ui/css/') ;
		
		// 默认的控制器
		$aAccessRouter = $this->accessRouter() ;
		$aAccessRouter->setDefaultController('oc\\base\\DefaultController') ;
	}
}

?>