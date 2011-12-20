<?php
namespace org\opencomb\platform\ext ;

use org\jecat\framework\fs\IFolder;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\lang\Object;

class ExtensionSetup extends Object
{
	public function install(IFolder $aExtensionFolder)
	{
		$aExtMgr = ExtensionManager::singleton() ;
		
		// 读取扩展的 metainfo 
		$aExtMeta = ExtensionMetainfo::load($aExtensionFolder) ;
		
		
		// 检查扩展是否已经安装
		if( $aInstalled=$aExtMgr->extensionMetainfo( $aExtMeta->name() ) )
		{
			if( $aExtMeta->version->compare($aInstalled->version())==0 )
			{
				throw new Exception("安装扩展操作退出：已经安装了扩展：%s(%s)",array($aExtMeta->name(),$aExtMeta->version())) ;
			}
		}
		
		// 检查依赖关系
		$this->checkDependence($aExtMeta) ;
		
		// 资源升级
		if($aInstalled)
		{
			$this->upgradeResource($aExtMeta) ;
		}
		
		// 安装资源
		else
		{
			$this->installResource($aExtMeta) ;
		}
	}
	
	public function checkDependence(ExtensionMetainfo $aExtMeta)
	{
		
	}
	
	/**
	 * 升级扩展的资源版本
	 */
	private function upgradeResource(ExtensionMetainfo $aExtMeta)
	{
		
	}
	
	/**
	 * 安装资源
	 */
	private function installResource(ExtensionMetainfo $aExtMeta)
	{
		$sDataSetupClass = $aExtMeta->dataSetupClass() ;
	}
	
}

?>