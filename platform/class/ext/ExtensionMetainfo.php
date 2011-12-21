<?php
namespace org\opencomb\platform\ext ;

use org\opencomb\platform\ext\dependence\Dependence;
use org\jecat\framework\util\VersionCompat;
use org\jecat\framework\lang\Type;
use org\jecat\framework\fs\IFolder;
use org\jecat\framework\fs\FileSystem;
use org\jecat\framework\util\VersionExcetion;
use org\jecat\framework\util\String;
use org\jecat\framework\util\Version;
use org\opencomb\platform\ext\coreuser\subscribe\Create;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\system\Application;
use org\jecat\framework\resrc\HtmlResourcePool;
use org\opencomb\platform\Platform;
use org\jecat\framework\ui\xhtml\UIFactory ;
use org\jecat\framework\resrc\htmlresrc\HtmlResourcePoolFactory;
use org\jecat\framework\lang\Object;

class ExtensionMetainfo extends Object
{
	/**
	 * @param $extensionFoler	string,IFolder
	 * @return ExtensionMetainfo
	 */
	static public function load($extensionFoler)
	{
		if( is_string($extensionFoler) )
		{
			$sExtPath = $extensionFoler ;
			if( !$aExtFolder = FileSystem::singleton()->findFolder($sExtPath) )
			{
				throw new ExtensionException("无法读取扩展信息，扩展路径无效：%s",$sExtPath) ;
			}
		}
		else if( $extensionFoler instanceof IFolder )
		{
			$sExtPath = $extensionFoler->path() ;
			$aExtFolder = $extensionFoler ;
		}
		else
		{
			throw new Exception("参数 \$extensionFoler 类型无效，必须是 string, IFolder 类型，传入类型为 %s",Type::reflectType($extensionFoler)) ;
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
		foreach( array('name','version','title') as $sNodeName )
		{
			if(empty($aDomMetainfo->$sNodeName))
			{
				throw new ExtensionException(
						"扩展 metainfo 文件内容无效，缺少必须的 %s 元素：%s"
						, array($sNodeName,$aMetainfoFile->url())
				) ;
			}
		}

		// --------------
		// name,version,class,title,sExtensionPath
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
		$aExtMetainfo->sTitle = (string)$aDomMetainfo->title ;
		$aExtMetainfo->sDescription = (string)$aDomMetainfo->description ;
		
		// compat version
		$aExtMetainfo->aVersionCompat = new VersionCompat() ;
		$aExtMetainfo->aVersionCompat->addCompatibleVersion($aExtMetainfo->version()) ;
		
		// priority
		if(!empty($aDomMetainfo->priority))
		{
			$nPriority = (int) $aDomMetainfo->priority ;
			if( $nPriority<1 or $nPriority>9 )
			{
				throw new ExtensionException(
						"扩展 metainfo 文件中的 priority 节点必须是一个大于0，小于9的整数，扩展 %s 提供的内容为：%s"
						, array($aDomMetainfo->name,$aDomMetainfo->priority)
				) ;
			}
			$aExtMetainfo->nPriority = $nPriority ;
		}
		
		
		// data version, setup, upgrades
		// --------------
		//  data version
		if(!empty($aDomMetainfo->data->version))
		{
			$aExtMetainfo->aDataVersion = Version::FromString((string)$aDomMetainfo->data->version) ;
		}
		else
		{
			$aExtMetainfo->aDataVersion = clone $aExtMetainfo->aVersion ;
		}
		//  data setup
		if(!empty($aDomMetainfo->data->setup))
		{
			$aExtMetainfo->sDataSetupClass = (string)$aDomMetainfo->data->setup ;
		}
		//  data upgrade
		if(!empty($aDomMetainfo->data->upgrader))
		{
			foreach($aDomMetainfo->xpath('/data/upgrader') as $aUpgrader)
			{
				if(empty($aUpgrader['from']))
				{
					throw new ExtensionException("扩展%s的metainfo.xml文件中 data/upgrader 节点缺少 from 属性",$aDomMetainfo->name) ;
				}
				if(empty($aUpgrader['to']))
				{
					throw new ExtensionException("扩展%s的metainfo.xml文件中 data/upgrader 节点缺少 to 属性",$aDomMetainfo->name) ;
				}
				$aExtMetainfo->arrDataUpgraderClasses[] = array(
							'from' => Version::FromString((string)$aUpgrader['from']) ,
							'to' => Version::FromString((string)$aUpgrader['to']) ,
							'class' => (string)$aUpgrader
				) ;
			}
		}
		
		// package
		// --------------
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
		// --------------
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
		// --------------
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
		// --------------
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
		
		// dependence
		// --------------
		try{
			$aExtMetainfo->aDependence = Dependence::loadFromXml($aDomMetainfo) ;
		}catch(Exception $e){
			throw new ExtensionException("扩展%s的metainfo.xml存在错误",$aDomMetainfo->name,$e) ;
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
	
	public function title()
	{
		return $this->sTitle ;
	}
	
	public function description()
	{
		return $this->sDescription ;
	}
	
	public function className()
	{
		return $this->sClassName?: 'org\\opencomb\\platform\\ext\\Extension' ;
	}
	
	/**
	 * @return org\jecat\framework\util\Version
	 */
	public function version()
	{
		return $this->aVersion ;
	}
	
	/**
	 * @return org\jecat\framework\util\VersionCompat
	 */
	public function versionCompat()
	{
		return $this->aVersionCompat ;
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
	
	public function priority()
	{
		$this->nPriority ;
	}
	
	/**
	 * @return org\jecat\framework\util\Version
	 */
	public function dataVersion()
	{
		$this->aDataVersion ;
	}
	
	public function dataSetupClass()
	{
		$this->sDataSetupClass ;
	}
	
	public function dataUpgradeClassIterator()
	{
		return new \ArrayIterator($this->arrDataUpgraderClasses) ;
	}
	
	/**
	 * @return org\opencomb\platform\ext\dependence\Dependence
	 */
	public function dependence()
	{
		return $this->aDependence ;
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
	private $aVersionCompat ;
	private $sTitle ;
	private $sDescription ;
	private $sClassName ;
	private $nPriority = 3 ;
	
	private $aDataVersion ;
	private $sDataSetupClass ;
	private $arrDataUpgraderClasses = array() ;
	
	private $arrPackages ;
	private $arrTemplateFolders = array() ;
	private $arrPublicFolders = array() ;
	private $arrBeanFolders = array() ;
	
	private $aDependence ;
	
}

?>