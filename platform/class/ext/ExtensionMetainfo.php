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
	
	public function classCompiledPackageFolder()
	{
		return '/extensions/'.$this->sName.'/compiled' ;
	}
	public function classPackageFolder()
	{
		return '/extensions/'.$this->sName.'/class' ;
	}
	
	public function resourceUiTemplateFolder()
	{
		return 'extensions/'.$this->sName.'/ui/template/' ;
	}
	
	public function resourceUiJsFolder()
	{
		return 'extensions/'.$this->sName.'/ui/js/' ;
	}
	
	public function resourceUiCssFolder()
	{
		return 'extensions/'.$this->sName.'/ui/css/' ;
	}
	
	public function className()
	{
		return $this->sClassName ;
	}
	
}

?>