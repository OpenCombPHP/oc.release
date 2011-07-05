<?php
namespace oc ;

use jc\ui\xhtml\UIFactory ;
use jc\mvc\view\UIFactory as MvcUIFactory ;
use oc\ui\SourceFileManager;
use jc\system\Application;
use oc\system\PlatformFactory ;
use jc\resrc\htmlresrc\HtmlResourcePoolFactory;

class Platform extends Application
{
	public function __construct($sAppDir)
	{
		parent::__construct() ;
		
		if( !Application::singleton(false) )
		{
			Application::setSingleton($this) ;
		}
		
		$aAppFactory = new PlatformFactory() ;
		$aAppFactory->build($this) ;	
		
		// app dir
		$this->setApplicationDir($sAppDir) ;
		
		$aSrcFileMgr = new SourceFileManager() ;
		UIFactory::singleton()->setSourceFileManager($aSrcFileMgr) ;
		MvcUIFactory::singleton()->setSourceFileManager($aSrcFileMgr) ;
		
		$aSrcFileMgr->addFolder($sAppDir.'/platform/ui/template/','oc') ;
		$aSrcFileMgr->addFolder(\jc\PATH.'src/template/','jc') ;
		
		//UIFactory::singleton()->sourceFileManager()->addFolder($sAppDir.'/platform/ui/template/','oc') ;
		
		HtmlResourcePoolFactory::singleton()->javaScriptFileManager()->addFolder($sAppDir.'/platform/ui/js/') ;
		HtmlResourcePoolFactory::singleton()->cssFileManager()->addFolder($sAppDir.'/platform/ui/css/') ;
		
		// 默认的控制器
		$aAccessRouter = $this->accessRouter() ;
		$aAccessRouter->setDefaultController('oc\\base\\DefaultController') ;
	}
}

?>