<?php
namespace org\opencomb\platform\ext ;

use org\jecat\framework\fs\FSO;
use org\jecat\framework\fs\FSIterator;
use org\opencomb\platform\ext\dependence\Dependence;
use org\jecat\framework\util\VersionCompat;
use org\jecat\framework\util\VersionScope;
use org\jecat\framework\lang\Type;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\util\VersionExcetion;
use org\jecat\framework\util\String;
use org\jecat\framework\util\Version;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\lang\Object;
use org\opencomb\platform as oc;
use org\jecat\framework\setting\Setting;

class ExtensionMetainfo extends Object
{
	/**
	 * @param $extensionFoler	string,Folder
	 * @return ExtensionMetainfo
	 */
	static public function load($extensionFoler,$sHttpUrl=null)
	{
		if( is_string($extensionFoler) )
		{
			if( substr($extensionFoler,0,1)!=='/' and strpos($extensionFoler,':')===false )
			{
				$sExtPath = oc\EXTENSIONS_FOLDER . '/' . $extensionFoler ;
			}
			else
			{
				$sExtPath = $extensionFoler ;
			}
			
			$aExtFolder = new Folder($sExtPath);
			if( !$aExtFolder->exists() )
			{
				throw new ExtensionException("无法读取扩展信息，扩展路径无效：%s",$sExtPath) ;
			}
		}
		else if( $extensionFoler instanceof Folder )
		{
			$sExtPath = $extensionFoler->path() ;
			$aExtFolder = $extensionFoler ;
		}
		else
		{
			throw new Exception("参数 \$extensionFoler 类型无效，必须是 string, Folder 类型，传入类型为 %s",Type::reflectType($extensionFoler)) ;
		}
		
		if( !$aMetainfoFile = $aExtFolder->findFile('metainfo.xml') )
		{
			throw new ExtensionException("扩展无效，缺少 metainfo 文件：%s",$sExtPath) ;
		}
		
		$aMetainfoContents = new String() ;
		$aMetainfoFile->openReader()->readInString($aMetainfoContents) ;
		
		if( !$aDomMetainfo = simplexml_load_string($aMetainfoContents) )
		{
			throw new ExtensionException("扩展 metainfo 文件内容无效：%s",$aMetainfoFile->path()) ;
		}
		
		return self::loadFromXML($aDomMetainfo,$sExtPath,$sHttpUrl);
	}
	
	static public function loadFromXML(\SimpleXMLElement $aDomMetainfo,$sExtPath='',$sHttpUrl=null){
		// 检查必须的参数
		foreach( array('name','version','title') as $sNodeName )
		{
			if(empty($aDomMetainfo->$sNodeName))
			{
				throw new ExtensionException(
						"扩展 metainfo 文件内容无效，缺少必须的 %s 元素：%s"
						, array($sNodeName,$aMetainfoFile->path())
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
		if(!empty($aDomMetainfo->versionCompat)){
			$strVersionCompat = (string)$aDomMetainfo->versionCompat;
			$arrVersionCompat = preg_split('/[\s]+/',$strVersionCompat,-1,PREG_SPLIT_NO_EMPTY);
			foreach($arrVersionCompat as $strScope){
				$aVersionScope = VersionScope::fromString($strScope);
				$aExtMetainfo->aVersionCompat->addCompatibleVersionScope($aVersionScope);
			}
		}
		
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
		if(!empty($aDomMetainfo->data->installer))
		{
			$aExtMetainfo->sDataInstallerClass = (string)$aDomMetainfo->data->installer ;
		}
		//  data upgrade
		if(!empty($aDomMetainfo->data->upgrader))
		{
			foreach($aDomMetainfo->xpath('data/upgrader') as $aUpgrader)
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
			
			$sFolder = (string)$aPackage['folder'] ;
			Folder::formatPath($sFolder) ;
			$aExtMetainfo->arrPackages[] = array($sFolder,$sNamespace) ;
		}
		/*
			如果在metainfo.xml未定义package，则自动检测。
			检测规则：寻找/packages目录。如果找到，则packages下的目录是package，目录名是namespace。
		*/
		if( empty( $aExtMetainfo->arrPackages ) ){
			$aPackagesFolder = new Folder( $sExtPath.'/packages');
			if( $aPackagesFolder->exists() ){
				$aFolderIter = $aPackagesFolder->iterator(FSIterator::CONTAIN_FOLDER | FSIterator::RETURN_FSO);
				foreach($aFolderIter as $aSubFolder ){
					$sNamespace = $aSubFolder->name();
					$sNamespace = str_replace('.','\\',$sNamespace) ;
					$sNamespace = str_replace('/','\\',$sNamespace) ;
					
					$sFolder = '/packages/'.$aSubFolder->name();
					Folder::formatPath($sFolder) ;
					$aExtMetainfo->arrPackages[] = array($sFolder,$sNamespace) ;
				}
			}
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
		/*
			如果在metainfo.xml中未定义template,public,bean，则自动检测。
		*/
		$arrPtg = array('template','public','bean');
		foreach($arrPtg as $sPtg){
			$sPhPtg = ucwords($sPtg);
			$sMemberName = 'arr'.$sPhPtg.'Folders';
			$arrMember = & $aExtMetainfo->$sMemberName;
			if( empty( $arrMember ) ){
				// 复数
				$aMultiFolder = new Folder( $sExtPath.'/'.$sPtg.'s' );
				if( $aMultiFolder->exists() ){
					$aFolderIter = $aMultiFolder->iterator( FSIterator::CONTAIN_FOLDER | FSIterator::RETURN_FSO );
					foreach( $aFolderIter as $aSubFolder ){
						$sNamespace = $aSubFolder->name() ;
						$sNamespace = str_replace('.','\\',$sNamespace) ;
						$sNamespace = str_replace('/','\\',$sNamespace) ;
					
						$sFolder = '/'.$sPtg.'s/'.$aSubFolder->name();
						Folder::formatPath($sFolder) ;
						$arrMember [] = array($sFolder,$sNamespace) ;
					}
				}
			
				// 单数
				$aSingleFolder = new Folder( $sExtPath.'/'.$sPtg );
				if( $aSingleFolder->exists() ){
					$sNamespace = $sExtName ;
					$sFolder = '/'.$sPtg;
					$arrMember[] = array($sFolder,$sNamespace);
				}
			}
		}
		
		// dependence
		// --------------
		try{
			$aExtMetainfo->aDependence = Dependence::loadFromXml($aDomMetainfo) ;
		}catch(Exception $e){
			throw new ExtensionException("扩展%s的metainfo.xml存在错误:%s",array($aDomMetainfo->name,$e->message())) ;
		}
		
		// licences
		// --------------
		if(!empty($aDomMetainfo->licences['folder']))
		{
			$aExtMetainfo->sLicencesFolder = (string)$aDomMetainfo->licences['folder'] ;
		}
		
		return $aExtMetainfo ;
	}
	
	public function __construct($sName,Version $aVersion,$sExtPath,$sClass=null)
	{
		parent::__construct() ;
		
		$this->sName = $sName ;
		$this->aVersion = $aVersion ;
		$this->sClassName = $sClass ;
		$this->setInstallPath($sExtPath) ;
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

	public function setInstallPath($sFolderPath,$sHttpUrl=null)
	{
		// 计算 http url
		if($sHttpUrl===null)
		{
			$sHttpUrl = FSO::relativePath(oc\EXTENSIONS_FOLDER,$sFolderPath) ;
			if(strpos($sHttpUrl,'..')===false)
			{
				$sHttpUrl = oc\EXTENSIONS_URL.'/'.$sHttpUrl ;
			}
		}
		
		$this->sHttpUrl = $sHttpUrl ;
		$this->sExtensionPath = $sFolderPath ;
	}
	public function installPath()
	{
		return $this->sExtensionPath ;
	}
	
	
	/**
	 * @return \Iterator
	 */
	public function packageIterator()
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
	
	public function priority()
	{
		return $this->nPriority ;
	}
	
	public function setting()
	{
		return Setting::singleton()->separate('extensions/'.$this->name()) ;
	}
	
	/**
	 * @return org\jecat\framework\util\Version
	 */
	public function dataVersion()
	{
		return $this->aDataVersion ;
	}
	
	public function dataInstallerClass()
	{
		return $this->sDataInstallerClass ;
	}
	
	public function dataUpgradeClassIterator()
	{
		return new \ArrayIterator($this->arrDataUpgraderClasses) ;
	}
	
	public function httpUrl()
	{
		return $this->sHttpUrl ;
	}
	
	/**
	 * @return org\opencomb\platform\ext\dependence\Dependence
	 */
	public function dependence()
	{
		return $this->aDependence ;
	}
	
	public function licenceIterator()
	{
		if( !$this->sLicencesFolder )
		{
			return new \EmptyIterator() ;
		}
		
		$aFolder = new Folder($this->installPath().'/'.$this->sLicencesFolder) ;
		if( !$aFolder->exists() )
		{
			return new \EmptyIterator() ;
		}
		
		return $aFolder->iterator(FSIterator::RETURN_FSO|FSIterator::FILE) ; 
	}
	
	static public function formatPath($sPath)
	{
		$sPath = strval($sPath) ;
		
		 ;
		
		return $sPath ;
	}
	
	private $sName ;
	private $aVersion ;
	private $aVersionCompat ;
	private $sTitle ;
	private $sDescription ;
	private $sClassName ;
	private $nPriority = 3 ;
	private $sHttpUrl = null ;
	
	private $aDataVersion ;
	private $sDataInstallerClass ;
	private $arrDataUpgraderClasses = array() ;
	
	/*
		下面四个，统一成 folder在前，namespace在后。
	*/
	private $arrPackages = array();
	private $arrTemplateFolders = array() ;
	private $arrPublicFolders = array() ;
	private $arrBeanFolders = array() ;
	private $sLicencesFolder ;
	
	private $aDependence ;
	
}

