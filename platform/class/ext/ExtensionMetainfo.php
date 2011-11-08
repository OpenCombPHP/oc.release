<?php
namespace oc\ext ;

use jc\util\VersionExcetion;

use jc\util\String;

use jc\util\Version;
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
	/**
	 * @return ExtensionMetainfo
	 */
	static public function load($sExtPath)
	{
		if( !$aExtFolder = Application::singleton()->fileSystem()->findFolder($sExtPath) )
		{
			throw new ExtensionException("扩展路径无效：%s",$sExtPath) ;
		}
		
		if( !$aMetainfoFile = $aExtFolder->findFile('metainfo.xml') )
		{
			throw new ExtensionException("扩展无效，缺少 metainfo 文件：%s",$sExtPath) ;
		}
		
		$aMetainfoContents = new String() ;
		$aMetainfoFile->openReader()->readInString($aMetainfoContents) ;
		
		if( !$aDomMetainfo = simplexml_load_string($aMetainfoContents) )
		{
			throw new ExtensionException("扩展 metainfo 文件内容无效：%s",$aMetainfoFile->url()) ;
		}
		
		// 检查必须的参数
		foreach( array('name','version') as $sNodeName )
		{
			if(empty($aDomMetainfo->$sNodeName))
			{
				throw new ExtensionException(
						"扩展 metainfo 文件内容无效，缺少必须的  元素：%s"
						, array($aMetainfoFile->url(),$sNodeName)
				) ;
			}
		}

		// --------------
		// name,version,class
		try{
			new self(
				$aDomMetainfo->name
				, Version::FromString($aDomMetainfo->version)
				, empty($aDomMetainfo->class)? null: str_replace(trim($aDomMetainfo->class),'.','\\')
			) ;
		}
		catch (VersionExcetion $e)
		{
			throw new ExtensionException(
					"扩展 %s 的 metainfo 文件中定义的 version 格式无效：%s"
					, array($aDomMetainfo['Extension']['name'],$aDomMetainfo['Extension']['version'])
					, $e
			) ;
		}
		
		// package
		foreach($aDomMetainfo->xpath('/Extension/package') as $sPackage)
		{
			echo $sPackage['folder'] ;
		}
	}
	
	public function __construct($sName,Version $aVersion)
	{
		parent::__construct() ;
		
		$this->sName = $sName ;
		$this->aVersion = $aVersion ;
	}

	public function name()
	{
		return $this->sName ;
	}
	
	/**
	 * @return jc\util\Version
	 */
	public function version()
	{
		return $this->aVersion ;
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
		
		$sPath = '/data/compiled/class/extensions/'.$this->sName.'/'.$this->version() ;
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
	
	private $sName ;
	private $aVersion ;
	private $sClassName ;
}

?>