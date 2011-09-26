<?php
namespace oc\ext ;

use oc\ext\coreuser\subscribe\Create;

use jc\lang\Exception;
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

	public function installFolderPath()
	{
		return $this->sName ;
	}
	
	public function installFolder(Platform $aPlatform=null)
	{
		if(!$aPlatform)
		{
			$aPlatform = Application::singleton() ;
		}
		
		return $aPlatform->fileSystem()->find('/extensions/'.$this->sName) ;
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
		
		$sPath = '/extensions/'.$this->sName.'/compiled' ;
		if( !$aFolder=$aPlatform->fileSystem()->find($sPath) and !$aFolder=$aPlatform->fileSystem()->createFolder($sPath) )
		{
			throw new Exception(
				"无法为扩展(%s)创建class编译目录：%s，请检查文件系统上的权限。"
				, array($this->name(),$sPath)
			) ;
		}
		
		return $aFolder ;
	}
	public function classPackageFolder(Platform $aPlatform=null)
	{
		if(!$aPlatform)
		{
			$aPlatform = Application::singleton() ;
		}
		
		$aClassFolder = $aPlatform->fileSystem()->find('/extensions/'.$this->sName.'/class') ;
		
		if(!$aClassFolder)
		{
			throw new Exception("找不到扩展（%s）的源文件目录",$this->sName) ;
		}
		
		return $aClassFolder ;
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

	public function publicDataFolder()
	{
		$aFilesystem = Application::singleton()->fileSystem() ;
		
		if( !$aFolder=$aFilesystem->find('/data/public/'.$this->sName) )
		{
			$aFolder = $aFilesystem->createFolder('/data/public/'.$this->sName) ;
		}
		
		return $aFolder ;
	}
}

?>