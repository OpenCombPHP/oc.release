<?php
namespace oc\ext ;

use oc\Platform;
use jc\ui\xhtml\Factory as UIFactory;
use jc\mvc\view\htmlresrc\HtmlResourcePoolFactory;
use jc\lang\Object;

class ExtensionMetainfo extends Object
{
	public function __construct($sName,$sClassName)
	{
		parent::__construct() ;
		
		$this->sName = $sName ;
		$this->sClassName = $sClassName ;
	}

	public function name()
	{
		return $this->sName ;
	}
	
	public function installFolder()
	{
		return $this->sName.'/' ;
	}
	
	public function classPackageNamespace()
	{
		return 'oc\\ext\\'.$this->sName ;
	}
	
	public function classPackageFolder()
	{
		return 'extensions/'.$this->sName.'/class/' ;
	}
	
	public function resourceUiTemplateFolder()
	{
		return 'extensions/'.$this->sName.'/ui/template/' ;
	}
	
	public function resourceUiJsFolder()
	{
		return 'extensions/'.$this->sName.'/ui/template/' ;
	}
	
	public function resourceUiCssFolder()
	{
		return 'extensions/'.$this->sName.'/ui/template/' ;
	}
	
	public function className()
	{
		return $this->sClassName ;
	}
	
	public function load(Platform $aPlatform)
	{		
		$sPlatformDir = $aPlatform->applicationDir() ;
		
		// 加载类包
		$aPlatform->classLoader()->addPackage(
				$sPlatformDir.$this->classPackageFolder()
				, $this->classPackageNamespace()
		) ;
		
		// 注册ui模板目录
		UIFactory::singleton()->sourceFileManager()->addFolder(
				$sPlatformDir.$this->resourceUiTemplateFolder()
		) ;
		
		// 注册 js/css 目录
		HtmlResourcePoolFactory::singleton()->javaScriptFileManager()->addFolder(
				$sPlatformDir.$this->resourceUiJsFolder()
		) ;
		HtmlResourcePoolFactory::singleton()->cssFileManager()->addFolder(
				$sPlatformDir.$this->resourceUiCssFolder()
		) ;
		
		$sClass = $this->className() ;		
		$aExtension = new $sClass($this) ;
		$aExtension->setApplication($aPlatform) ;
		
		$aExtension->load() ;
		
		return $aExtension ;
	}
}

?>