<?php
namespace org\opencomb\platform\ext ;

use org\opencomb\platform\Platform;
use org\jecat\framework\lang\Object;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\bean\BeanFactory;
use org\jecat\framework\mvc\view\UIFactory;

class ExtensionLoader extends Object
{
	public function loadAllExtensions(Platform $aPlatform,ExtensionManager $aExtensionManager)
	{
		foreach($aExtensionManager->extensionPriorities() as $nPriority)
		{
			foreach($aExtensionManager->enableExtensionNameIterator($nPriority) as $sExtName)
			{
				$this->loadExtension($aPlatform,$aExtensionManager,$sExtName,$nPriority) ;
			}
		}
	}
	
	public function loadExtension(Platform $aPlatform,ExtensionManager $aExtensionManager,$sName,$nPriority=-1)
	{
		if(!$aExtMeta = $aExtensionManager->extensionMetainfo($sName))
		{
			throw new ExtensionException("扩展尚未安装：%s，无法完成加载",$sName) ;
		}
		$sVersion = $aExtMeta->version()->toString(false) ;
		$aPlatform = $aExtensionManager->application() ;
		$aPlatformFs = Folder::singleton() ;

		// 加载类包
		foreach($aExtMeta->packageIterator() as $arrPackage)
		{
			list($sNamespace,$sPackagePath) = $arrPackage ;
			
			$sPackagePath = $aExtMeta->installPath().$sPackagePath ;
			ClassLoader::singleton()->addPackage( $sNamespace, $aPlatformFs->findFolder($sPackagePath) ) ;
			
			$aExtensionManager->registerPackageNamespace($sNamespace,$sName) ;
		}
		
		// 注册模板目录
		foreach($aExtMeta->templateFolderIterator() as $arrTemplateFolder)
		{
			list($sFolder,$sNamespace) = $arrTemplateFolder ;
			if( !$aFolder=$aPlatformFs->findFolder( $aExtMeta->installPath().$sFolder ) )
			{
				throw new ExtensionException("扩展 %s 的模板目录 %s 不存在",array($sName,$sFolder)) ;
			}
			UIFactory::singleton()->sourceFileManager()->addFolder($aFolder,$sNamespace) ;	
		}
		
		// 注册 public 目录
		foreach($aExtMeta->publicFolderIterator() as $arrPublicFolder)
		{
			list($sFolder,$sNamespace) = $arrPublicFolder ;
			if( !$aFolder=$aPlatformFs->findFolder( $aExtMeta->installPath().$sFolder ) )
			{
				throw new ExtensionException("扩展 %s 的公共文件目录 %s 不存在",array($sName,$sFolder)) ;
			}
			$aFolder->setHttpUrl($aExtMeta->installPath().$sFolder) ;
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
		
		// 建立扩展实例
		$aExtension = $aExtensionManager->extension($sName) ;
		
		// 注册 Extension::flyweight()
		Extension::setFlyweight($aExtension,$aExtension->metainfo()->name()) ;
		
		// 设置 priority
		if( $nPriority<0 )
		{
			$nPriority = end($aExtensionManager->extensionPriorities()) ;
		}
		$aExtension->setRuntimePriority($nPriority) ;
		
		// 执行扩展的加载函数
		$aExtension->load($aPlatform) ;
		
		return $aExtension ;
	}
	
	
	public function enableExtensions(Platform $aPlatform,ExtensionManager $aExtensionManager)
	{
		foreach($aExtensionManager->iterator() as $aExtension)
		{
			// 注册 Extension::flyweight()
			$sExtensionName = $aExtension->metainfo()->name() ;
			if( !Extension::flyweight($sExtensionName,false) )
			{
				Extension::setFlyweight($aExtension,$sExtensionName) ;
			}
			
			$aExtension->active($aPlatform) ;
		}
	}
	
}

