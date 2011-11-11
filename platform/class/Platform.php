<?php
namespace oc ;

use oc\ext\ExtensionManager;
use oc\ext\ExtensionMetainfo;
use oc\resrc\ResourceManager;
use jc\resrc\HtmlResourcePool;
use jc\ui\xhtml\UIFactory ;
use jc\system\Application;
use oc\system\PlatformFactory ;

class Platform extends Application
{
	public function load()
	{
		// 加载扩展
		$aExtensions = $this->extensions() ;
		foreach($aExtensions->enableExtensionNameIterator() as $sExtName)
		{
			$aExtensions->loadExtension($sExtName) ;
		}
		//foreach($this->extensions()->installExtension() as )
	}
		
	public function extensionsUrl()
	{
		return $this->sExtensionsFolder.'/' ;
	}
	public function extensionsDir()
	{
		return $this->applicationDir() . $this->sExtensionsFolder . '/' ;
	}
	
	/**
	 * @return oc\ext\ExtensionManager 
	 */
	public function extensions()
	{
		if( !$this->aExtensionManager )
		{
			$this->aExtensionManager = new ExtensionManager($this->setting()) ;
		}
		return $this->aExtensionManager ;
	}
	
	private $sExtensionsFolder = 'extensions' ;
	private $aExtensionManager ;
	private $aStaticPageManager ;
}

?>