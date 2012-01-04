<?php
namespace org\opencomb\platform\ext ;

use org\jecat\framework\lang\Object;
use org\jecat\framework\fs\FileSystem;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\bean\BeanFactory;
use org\jecat\framework\mvc\view\UIFactory;

class ExtensionLoader extends Object
{
	public function loadAllExtensions(ExtensionManager $aExtensionManager)
	{
		foreach($aExtensionManager->extensionPriorities() as $nPriority)
		{
			foreach($aExtensionManager->enableExtensionNameIterator($nPriority) as $sExtName)
			{
				$this->loadExtension($aExtensionManager,$sExtName,$nPriority) ;
			}
		}
	}
	
	public function loadExtension(ExtensionManager $aExtensionManager,$sName,$nPriority=-1)
	{
		if(!$aExtMeta = $aExtensionManager->extensionMetainfo($sName))
		{
			throw new ExtensionException("扩展尚未安装：%s，无法完成加载",$sName) ;
		}
		$sVersion = $aExtMeta->version()->toString(false) ;
		$aPlatform = $aExtensionManager->application() ;
		$aPlatformFs = FileSystem::singleton() ;

		// 加载类包
		foreach($aExtMeta->pakcageIterator() as $arrPackage)
		{
			list($sNamespace,$sPackagePath) = $arrPackage ;
			
			$sPackagePath = $aExtMeta->installPath().$sPackagePath ;
			ClassLoader::singleton()->addPackage( $sNamespace, $sPackagePath ) ;
			
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
		
		$aExtension = $aExtensionManager->extension($sName) ;
		$aExtension->load() ;
				
		
		// 设置 priority
		if( $nPriority<0 )
		{
			$nPriority = end($aExtensionManager->extensionPriorities()) ;
		}
		$aExtension->setRuntimePriority($nPriority) ;
		
		
		return $aExtension ;
	}
}

?>