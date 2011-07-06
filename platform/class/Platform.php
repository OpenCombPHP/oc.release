<?php
namespace oc ;

use oc\resrc\UrlResourceManager;
use jc\resrc\HtmlResourcePool;
use jc\ui\xhtml\UIFactory ;
use jc\mvc\view\UIFactory as MvcUIFactory ;
use oc\ui\SourceFileManager;
use jc\system\Application;
use oc\system\PlatformFactory ;

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
		
		// 模板文件
		$aSrcFileMgr = new SourceFileManager() ;
		UIFactory::singleton()->setSourceFileManager($aSrcFileMgr) ;
		MvcUIFactory::singleton()->setSourceFileManager($aSrcFileMgr) ;
		
		$aSrcFileMgr->addFolder($sAppDir.'/platform/ui/template/','oc') ;
		$aSrcFileMgr->addFolder(\jc\PATH.'src/template/','jc') ;
		
		// css/js 资源
		$aJsMgr = new UrlResourceManager() ;
		$aCssMgr = new UrlResourceManager() ;
		HtmlResourcePool::setSingleton( new HtmlResourcePool($aJsMgr,$aCssMgr) ) ;
		
		$aJsMgr->addFolder($sAppDir.'/platform/ui/js/','platform/ui/js/','oc') ;
		$aCssMgr->addFolder($sAppDir.'/platform/ui/css/','platform/ui/css/','oc') ;
		$aCssMgr->addFolder(\jc\PATH.'src/style/','framework/src/style/','jc') ;
		
		// 默认的控制器
		$aAccessRouter = $this->accessRouter() ;
		$aAccessRouter->setDefaultController('oc\\base\\DefaultController') ;
	}
}

?>