<?php
namespace oc\ext ;

use jc\system\Application;

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
	
	public function classCompiledPackageFolder(Platform $aPlatform=null)
	{
		if(!$aPlatform)
		{
			$aPlatform = Application::singleton() ;
		}
		
		return $aPlatform->fileSystem()->find('/extensions/'.$this->sName.'/compiled') ;
	}
	public function classPackageFolder(Platform $aPlatform=null)
	{
		if(!$aPlatform)
		{
			$aPlatform = Application::singleton() ;
		}
		
		return $aPlatform->fileSystem()->find('/extensions/'.$this->sName.'/class') ;
	}
	
	public function resourceUiTemplateFolder(Platform $aPlatform=null)
	{
		if(!$aPlatform)
		{
			$aPlatform = Application::singleton() ;
		}
		
		return $aPlatform->fileSystem()->find('/extensions/'.$this->sName.'/ui/template') ;
	}
	
	public function resourceUiJsFolder(Platform $aPlatform=null)
	{
		if(!$aPlatform)
		{
			$aPlatform = Application::singleton() ;
		}
		
		return $aPlatform->fileSystem()->find('/extensions/'.$this->sName.'/ui/js') ;
	}
	
	public function resourceUiCssFolder(Platform $aPlatform=null)
	{
		if(!$aPlatform)
		{
			$aPlatform = Application::singleton() ;
		}
		
		return $aPlatform->fileSystem()->find('/extensions/'.$this->sName.'/ui/css') ;
	}
	
	public function className()
	{
		return $this->sClassName ;
	}
	
}

?>