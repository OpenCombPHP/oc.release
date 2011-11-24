<?php
namespace org\opencomb\ext ;

use org\jecat\framework\fs\FileSystem;

use org\jecat\framework\util\VersionExcetion;

use org\jecat\framework\util\String;

use org\jecat\framework\util\Version;
use org\opencomb\ext\coreuser\subscribe\Create;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\system\Application;
use org\jecat\framework\resrc\HtmlResourcePool;
use org\opencomb\Platform;
use org\jecat\framework\ui\xhtml\UIFactory ;
use org\jecat\framework\resrc\htmlresrc\HtmlResourcePoolFactory;
use org\jecat\framework\lang\Object;

class ExtensionMetainfo extends Object
{
	/**
	 * @return ExtensionMetainfo
	 */
	static public function load($sExtPath)
	{
		if( !$aExtFolder = FileSystem::singleton()->findFolder($sExtPath) )
		{
			throw new ExtensionException("无法读取扩展信息，扩展路径无效：%s",$sExtPath) ;
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
		// name,version,class,sExtensionPath
		$sExtName = strval($aDomMetainfo->name) ;
		try{
			$aExtMetainfo = new self(
				$sExtName
				, Version::FromString($aDomMetainfo->version)
				, $sExtPath
				, empty($aDomMetainfo->class)? null: str_replace('.','\\',trim($aDomMetainfo->class))
			) ;
		}
		catch (VersionExcetion $e)
		{
			throw new ExtensionException(
					"扩展 %s 的 metainfo 文件中定义的 version 格式无效：%s"
					, array($aDomMetainfo->name,$aDomMetainfo->version)
					, $e
			) ;
		}
		
		// package
		foreach($aDomMetainfo->xpath('/Extension/package') as $aPackage)
		{
			if(empty($aPackage['folder']))
			{
				throw new ExtensionException("扩展%s的metainfo.xml文件中 package 节点缺少 folder 属性",$aDomMetainfo->name) ;
			}
			if(empty($aPackage['namespace']))
			{
				throw new ExtensionException("扩展%s的metainfo.xml文件中 package 节点缺少 namespace 属性",$aDomMetainfo->name) ;
			}
			
			$sNamespace = strval($aPackage['namespace']) ;
			$sNamespace = str_replace('.','\\',$sNamespace) ;
			$sNamespace = str_replace('/','\\',$sNamespace) ;
			
			
			$aExtMetainfo->arrPackages[] = array($sNamespace,self::formatPath($aPackage['folder'])) ;
		}
	
		// template
		foreach($aDomMetainfo->xpath('/Extension/template') as $nIdx=>$aNode)
		{
			if(empty($aNode['folder']))
			{
				throw new ExtensionException("扩展%s的metainfo.xml文件中的第%d个 template 节点缺少 folder 属性",array($nIdx,$aDomMetainfo->name)) ;
			}
			$sFolder = self::formatPath($aNode['folder']) ;
			$sNamespace = empty($aNode['for'])? $sExtName: trim($aNode['for']) ;

			$aExtMetainfo->arrTemplateFolders[] = array($sFolder,$sNamespace) ;
		}
		
		// public folder
		foreach($aDomMetainfo->xpath('/Extension/publicFolder') as $nIdx=>$aNode)
		{
			if(empty($aNode['folder']))
			{
				throw new ExtensionException("扩展%s的metainfo.xml文件中的第%d个 publicFolder 节点缺少 folder 属性",array($nIdx,$aDomMetainfo->name)) ;
			}
			$sFolder = self::formatPath($aNode['folder']) ;
			$sNamespace = empty($aNode['for'])? $sExtName: trim($aNode['for']) ;

			$aExtMetainfo->arrPublicFolders[] = array($sFolder,$sNamespace) ;
		}
		
		// bean folder
		foreach($aDomMetainfo->xpath('/Extension/beanFolder') as $nIdx=>$aNode)
		{
			if(empty($aNode['folder']))
			{
				throw new ExtensionException("扩展%s的metainfo.xml文件中的第%d个 beanFolder 节点缺少 folder 属性",array($nIdx,$aDomMetainfo->name)) ;
			}
			$sFolder = self::formatPath($aNode['folder']) ;
			$sNamespace = empty($aNode['for'])? $sExtName: trim($aNode['for']) ;

			$aExtMetainfo->arrBeanFolders[] = array($sFolder,$sNamespace) ;
		}
		
		
		return $aExtMetainfo ;
	}
	
	public function __construct($sName,Version $aVersion,$sExtPath,$sClass=null)
	{
		parent::__construct() ;
		
		$this->sName = $sName ;
		$this->aVersion = $aVersion ;
		$this->sExtensionPath = $sExtPath ;
		$this->sClassName = $sClass ;
	}

	public function name()
	{
		return $this->sName ;
	}
	
	public function className()
	{
		return $this->sClassName?: 'org\\opencomb\\ext\\Extension' ;
	}
	
	/**
	 * @return org\jecat\framework\util\Version
	 */
	public function version()
	{
		return $this->aVersion ;
	}
	
	public function installPath()
	{
		return $this->sExtensionPath ;
	}	
	
	/**
	 * @return \Iterator
	 */
	public function pakcageIterator()
	{
		return new \ArrayIterator($this->arrPackages) ;
	}
	
	/**
	 * @return \Iterator
	 */
	public function templateFolderIterator()
	{
		return new \ArrayIterator($this->arrTemplateFolders) ;
	}
	
	/**
	 * @return \Iterator
	 */
	public function publicFolderIterator()
	{
		return new \ArrayIterator($this->arrPublicFolders) ;
	}
	
	/**
	 * @return \Iterator
	 */
	public function beanFolderIterator()
	{
		return new \ArrayIterator($this->arrBeanFolders) ;
	}
	
	
	
	
	
	public function classCompiledPackageFolder(Platform $aPlatform=null)
	{
		if(!$aPlatform)
		{
			$aPlatform = Application::singleton() ;
		}
		
		$sPath = '/data/compiled/class/extensions/'.$this->sName.'/'.$this->version() ;
		if( !$aFolder=FileSystem::singleton()->find($sPath) and !$aFolder=FileSystem::singleton()->createFolder($sPath) )
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
		
		$aClassFolder = FileSystem::singleton()->find('/extensions/'.$this->sName.'/class') ;
		
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
		
		return FileSystem::singleton()->find('/extensions/'.$this->sName.'/ui/template') ;
	}
	
	public function resourceUiJsFolder(Platform $aPlatform=null)
	{
		if(!$aPlatform)
		{
			$aPlatform = Application::singleton() ;
		}
		
		return FileSystem::singleton()->find('/extensions/'.$this->sName.'/ui/js') ;
	}
	
	public function resourceUiCssFolder(Platform $aPlatform=null)
	{
		if(!$aPlatform)
		{
			$aPlatform = Application::singleton() ;
		}
		
		return FileSystem::singleton()->find('/extensions/'.$this->sName.'/ui/css') ;
	}
	
	/**
	 * 
	 * @return org\jecat\framework\fs\IFolder 
	 */
	public function publicDataFolder()
	{
		$aFilesystem = FileSystem::singleton() ;
		
		if( !$aFolder=$aFilesystem->find('/data/public/'.$this->sName) )
		{
			$aFolder = $aFilesystem->createFolder('/data/public/'.$this->sName) ;
		}
		
		return $aFolder ;
	}
	
	static public function formatPath($sPath)
	{
		$sPath = FileSystem::formatPath(strval($sPath)) ;
		if( substr($sPath,0,1)!=='/' )
		{
			$sPath = '/' . $sPath ;
		}
		
		return $sPath ;
	}
	
	private $sName ;
	private $aVersion ;
	private $sClassName ;
	
	private $arrPackages ;
	private $arrTemplateFolders = array() ;
	private $arrPublicFolders = array() ;
	private $arrBeanFolders = array() ;
	
}

?>