<?php
namespace org\opencomb\platform\ext ;

use org\jecat\framework\mvc\controller\Request;
use org\jecat\framework\fs\FSO;
use org\opencomb\platform\service\Service;
use org\jecat\framework\lang\Object;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\bean\BeanFactory;
use org\jecat\framework\mvc\view\UIFactory;
use org\opencomb\platform as oc;

class ExtensionLoader extends Object
{
	public function loadAllExtensions(Service $aService=null,ExtensionManager $aExtensionManager=null)
	{
		if(!$aService)
		{
			$aService = Service::singleton() ;
		}
		if(!$aExtensionManager)
		{
			$aExtensionManager = $aService->extensions() ;
		}
		
		foreach($aExtensionManager->extensionPriorities() as $nPriority)
		{
			foreach($aExtensionManager->enableExtensionNameIterator($nPriority) as $sExtName)
			{
				$this->loadExtension($aService,$aExtensionManager,$sExtName,$nPriority) ;
			}
		}
	}
	
	public function loadExtension(Service $aService,ExtensionManager $aExtensionManager,$sName,$nPriority=-1)
	{
		if(!$aExtMeta = $aExtensionManager->extensionMetainfo($sName))
		{
			throw new ExtensionException("扩展尚未安装：%s，无法完成加载",$sName) ;
		}
		$sVersion = $aExtMeta->version()->toString(false) ;
		$aService = $aExtensionManager->application() ;

		// 加载类包
		foreach($aExtMeta->packageIterator() as $arrPackage)
		{
			list($sNamespace,$sPackagePath) = $arrPackage ;
			
			$aPackageFolder = new Folder($aExtMeta->installPath().$sPackagePath) ;
			if(!$aPackageFolder->exists())
			{
				throw new ExtensionException("没有找到扩展 %s 的类包:%s",array($sName,$sPackagePath)) ;
			}
			ClassLoader::singleton()->addPackage( $sNamespace, $aPackageFolder ) ;
			
			$aExtensionManager->registerPackageNamespace($sNamespace,$sName) ;
		}
		
		// 注册模板目录
		foreach($aExtMeta->templateFolderIterator() as $arrTemplateFolder)
		{
			list($sFolder,$sNamespace) = $arrTemplateFolder ;
			$aFolder = new Folder($aExtMeta->installPath().$sFolder) ;
			if( !$aFolder->exists() )
			{
				throw new ExtensionException("扩展 %s 的模板目录 %s 不存在",array($sName,$sFolder)) ;
			}
			UIFactory::singleton()->sourceFileManager()->addFolder($aFolder,$sNamespace) ;	
		}
		
		// 注册 public 目录
		foreach($aExtMeta->publicFolderIterator() as $arrPublicFolder)
		{
			list($sFolder,$sNamespace) = $arrPublicFolder ;
			$aFolder = new Folder($aExtMeta->installPath().$sFolder) ;
			if( !$aFolder->exists() )
			{
				throw new ExtensionException("扩展 %s 的公共文件目录 %s 不存在",array($sName,$sFolder)) ;
			}
			if(!$aExtMeta->httpUrl())
			{
				throw new ExtensionException("扩展 %s 安装目录的 http url 设置缺失，无法提供其公共目录 %s 的http url",array($sName,$sFolder)) ;
			}
			$aFolder->setHttpUrl($aExtMeta->httpUrl() . '/' . ltrim($sFolder,'/')) ;
			$aService->publicFolders()->addFolder($aFolder,$sNamespace) ;
		}
		
		// 注册 bean 目录
		foreach($aExtMeta->beanFolderIterator() as $arrFolder)
		{
			list($sFolder,$sNamespace) = $arrFolder ;
			$aFolder = new Folder($aExtMeta->installPath().$sFolder) ;
			if( !$aFolder->exists() )
			{
				throw new ExtensionException("扩展 %s 的bean目录 %s 不存在",array($sName,$sFolder)) ;
			}
			BeanFactory::singleton()->beanFolders()->addFolder($aFolder,$sNamespace) ;
		}
		
		// 建立扩展实例
		$aExtension = $aExtensionManager->extension($sName) ;
		
		// 设置 priority
		if( $nPriority<0 )
		{
			$nPriority = end($aExtensionManager->extensionPriorities()) ;
		}
		$aExtension->setRuntimePriority($nPriority) ;
		
		// 执行扩展的加载函数
		$aExtension->load($aService) ;
		
		return $aExtension ;
	}
	
	
	public function enableExtensions(Service $aService,ExtensionManager $aExtensionManager)
	{
		foreach($aExtensionManager->iterator() as $aExtension)
		{			
			$aExtension->active($aService) ;
		}
	}
	
}


