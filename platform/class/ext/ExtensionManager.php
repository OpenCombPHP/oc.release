<?php
namespace oc\ext ;

use jc\fs\FileSystem;

use jc\lang\oop\ClassLoader;

use jc\bean\BeanFactory;

use jc\mvc\view\UIFactory;

use oc\ext\ExtensionManager;
use jc\db\ExecuteException;
use jc\util\VersionExcetion;
use jc\util\Version;
use jc\lang\Exception;
use jc\setting\Setting;
use oc\Platform;
use jc\lang\Object;

class ExtensionManager extends Object
{
	public function __construct(Setting $aSetting)
	{
		$this->arrInstalledExtensions = array() ;
		
		foreach( $aSetting->item("/extensions",'installeds')?: array()  as $sExtPath )
		{		
			$aExtension = ExtensionMetainfo::load($sExtPath) ;
			$this->arrInstalledExtensions[ $aExtension->name() ] = $aExtension ;
		}
		
		$this->arrEnableExtensiongNames = $aSetting->item("/extensions",'enable') ?: array() ;
		
		$this->aSetting = $aSetting ;
	}
	
	public function installExtension()
	{
		// todo
	}
	
	/**
	 * @return ExtensionMetainfo
	 */
	public function extensionMetainfo($sName)
	{
		return isset($this->arrInstalledExtensions[$sName])? $this->arrInstalledExtensions[$sName]: null ;
	}

	/**
	 * \Iterator
	 */
	public function metainfoIterator()
	{
		return new \ArrayIterator($this->arrInstalledExtensions) ;
	}
	
	/**
	 * \Iterator
	 */
	public function enableExtensionNameIterator()
	{
		return new \ArrayIterator($this->arrEnableExtensiongNames) ;
	}
	
	/**
	 * \Iterator
	 */
	public function iterator()
	{
		return new \ArrayIterator($this->arrExtensionInstances) ;
	}
	
	public function extension($sName) 
	{
		return isset($this->arrExtensionInstances[$sName])? $this->arrExtensionInstances[$sName]: null ;
	}
	
	public function add(Extension $aExt)
	{
		$this->arrExtensionInstances[$aExt->metainfo()->name()] = $aExt ;
	}

	public function loadExtension($sName)
	{
		if(!$aExtMeta = $this->extensionMetainfo($sName))
		{
			throw new ExtensionException("扩展尚未安装：%s，无法完成加载",$sName) ;
		}
		$sVersion = $aExtMeta->version()->toString(false) ;
		$aPlatform = $this->application() ;
		$aPlatformFs = FileSystem::singleton() ;

		// 加载类包
		foreach($aExtMeta->pakcageIterator() as $arrPackage)
		{
			list($sNamespace,$sPackagePath) = $arrPackage ;
			
			$sPackageCompiledPath = "/data/compiled/class/extensions/{$sName}/{$sVersion}/".str_replace('\\','.',$sNamespace) ;
			$sPackagePath = $aExtMeta->installPath().$sPackagePath ;
			
			ClassLoader::singleton()->addPackage( $sNamespace, $sPackagePath, $sPackageCompiledPath ) ;
			
			$this->arrExtensionPackages[$sNamespace] = $sName ;
		}
		
		// 注册模板目录
		foreach($aExtMeta->templateFolderIterator() as $arrTemplateFolder)
		{
			list($sFolder,$sNamespace) = $arrTemplateFolder ;
			if( !$aFolder=$aPlatformFs->findFolder( $aExtMeta->installPath().$sFolder ) )
			{
				throw new ExtensionException("扩展 %s 的模板目录 %s 不存在",array($sName,$sFolder)) ;
			}
			$sCompiledPath = "/data/compiled/template/extensions/{$sName}/{$sVersion}/".str_replace('\\','.',$sNamespace) ;
			if( !$aCompiledFolder=$aPlatformFs->findFolder($sCompiledPath) and !$aCompiledFolder=$aPlatformFs->createFolder($sCompiledPath) )
			{
				throw new ExtensionException("无法为扩展 %s 创建模板编译目录:%s",array($sName,$sCompiledPath)) ;
			}
			UIFactory::singleton()->sourceFileManager()->addFolder($aFolder,$aCompiledFolder,$sNamespace) ;	
		}
		
		// 注册 js/css 目录
		foreach($aExtMeta->publicFolderIterator() as $arrPublicFolder)
		{
			list($sFolder,$sNamespace) = $arrPublicFolder ;
			if( !$aFolder=$aPlatformFs->findFolder( $aExtMeta->installPath().$sFolder ) )
			{
				throw new ExtensionException("扩展 %s 的公共文件目录 %s 不存在",array($sName,$sFolder)) ;
			}
			$aPlatform->publicFolders()->addFolder($aFolder,$sNamespace) ;
		}
		
		// 注册 bean 目录
		foreach($aExtMeta->beanFolderIterator() as $arrFolder)
		{
			list($sFolder,$sNamespace) = $arrFolder ;
			if( !$aFolder=$aPlatformFs->findFolder( $aExtMeta->installPath().$sFolder ) )
			{
				throw new ExtensionException("扩展 %s 的bean目录 %s 不存在",array($sName,$sFolder)) ;
			}
			BeanFactory::singleton()->beanFolders()->addFolder($aFolder,$sNamespace) ;
		}
		
		$sClass = $aExtMeta->className() ;
		if(!class_exists($sClass))
		{
			throw new ExtensionException("找不到扩展 %s 指定的扩展类: %s",array($sName,$sClass)) ;
		}
		$aExtension = new $sClass($aExtMeta) ;
		$aExtension->setApplication($aPlatform) ;
				
		$aExtension->load() ;
		
		$this->add($aExtension) ;
		
		return $aExtension ;
	}
	
	public function extensionNameByClass($sClass)
	{
		$nClassLen = strlen($sClass) ;
			
		for(end($this->arrExtensionPackages);$sNamespace=key($this->arrExtensionPackages);prev($this->arrExtensionPackages))
		{
			$nNamespaceLen = strlen($sNamespace) ;
			if( $nClassLen>$nNamespaceLen and substr($sClass,0,$nNamespaceLen)==$sNamespace and substr($sClass,$nNamespaceLen,1)=='\\' )
			{
				return current($this->arrExtensionPackages) ;
			}
		}
	}
	
	private $arrEnableExtensiongNames = array() ;
	
	private $arrInstalledExtensions = array() ;
		
	private $arrExtensionInstances = array() ;
	
	private $arrExtensionPackages = array() ;
	
	private $aSetting ;
}

?>