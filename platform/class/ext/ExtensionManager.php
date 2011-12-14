<?php
namespace org\opencomb\ext ;

use org\jecat\framework\fs\FileSystem;

use org\jecat\framework\lang\oop\ClassLoader;

use org\jecat\framework\bean\BeanFactory;

use org\jecat\framework\mvc\view\UIFactory;

use org\opencomb\ext\ExtensionManager;
use org\jecat\framework\db\ExecuteException;
use org\jecat\framework\util\VersionExcetion;
use org\jecat\framework\util\Version;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\setting\Setting;
use org\opencomb\Platform;
use org\jecat\framework\lang\Object;

class ExtensionManager extends Object
{
	public function __construct(Setting $aSetting=null)
	{
		if(!$aSetting)
		{
			$aSetting = Setting::singleton() ;
		}
		
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
			
			$sPackagePath = $aExtMeta->installPath().$sPackagePath ;
			ClassLoader::singleton()->addPackage( $sNamespace, $sPackagePath ) ;
			
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
		
		// 注册 public 目录
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
		$this->add($aExtension) ;
				
		$aExtension->load() ;
				
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