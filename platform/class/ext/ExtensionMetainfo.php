<?php
namespace oc\ext ;

use jc\resrc\HtmlResourcePool;

use oc\Platform;
use jc\ui\xhtml\UIFactory ;
use jc\resrc\htmlresrc\HtmlResourcePoolFactory;
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
		
		$sName = $this->name() ;
		
		// 加载类包
		$aPlatform->classLoader()->addPackage(
				$sPlatformDir.$this->classPackageFolder()
				, $this->classPackageNamespace()
		) ;
		
		// 注册ui模板目录
		UIFactory::singleton()->sourceFileManager()->addFolder(
				$sPlatformDir.$this->resourceUiTemplateFolder()
				, $sName
		) ;
		
		// 注册 js/css 目录
		HtmlResourcePool::singleton()->javaScriptFileManager()->addFolder(
				$sPlatformDir.$this->resourceUiJsFolder()
				, "extensions/{$sName}/ui/js/"
				, $sName
		) ;
		HtmlResourcePool::singleton()->cssFileManager()->addFolder(
				$sPlatformDir.$this->resourceUiCssFolder()
				, "extensions/{$sName}/ui/css/"
				, $sName
		) ;
		
		$sClass = $this->className() ;		
		$aExtension = new $sClass($this) ;
		$aExtension->setApplication($aPlatform) ;
		
		$aExtension->load() ;
		
		return $aExtension ;
	}
}

?>