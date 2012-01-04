<?php
namespace org\opencomb\platform\ext ;

use org\opencomb\platform\system\PlatformFactory;

use org\opencomb\platform\Platform;

use org\jecat\framework\setting\Setting;

use org\jecat\framework\fs\IFolder;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\lang\Object;
use org\jecat\framework\message\MessageQueue;

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
			if( $aExtMeta->version()->compare($aInstalled->version())==0 )
			{
				throw new Exception("安装扩展操作退出，无法重复安装扩展：%s(%s version:%s installed version:%s)",array($aExtMeta->title(),$aExtMeta->name(),$aExtMeta->version(),$aInstalled->version())) ;
			}
		}
		
		// 检查依赖关系
		$this->checkDependence($aExtMeta,false) ;
		
		// 加载新扩展的类包
		$this->loadClassPackages($aExtMeta) ;
		
		// 资源升级
		if( $aInstalled or $this->dataVersion($aExtMeta->name()) )	// 已经安装同名扩展，或系统中保留此扩展数据
		{
			$this->upgradeData($aExtMeta) ;
		}
		
		// 安装资源
		else
		{
			$this->installData($aExtMeta) ;
		}
		
		// 设置 setting
		$arrInstalled = Setting::singleton()->item('/extensions','installeds') ;
		$arrInstalled[] = $aExtensionFolder->path() ;
		Setting::singleton()->setItem('/extensions','installeds',$arrInstalled) ;
		
		// 添加扩展的安装信息
		$aExtMgr->addInstalledExtension($aExtMeta) ;
		
		// 清理系统缓存
		PlatformFactory::singleton()->clearRestoreCache(Platform::singleton()) ;
		
		// 卸载新扩展的类包
		$this->unloadClassPackages($aExtMeta) ;
		
		return $aExtMeta ;
	}
	
	public function enable($sExtName , MessageQueue $aMessageQueue)
	{
		$aExtMgr = ExtensionManager::singleton() ;
		
		// 已经安装
		if($aExtMgr->extension($sExtName))
		{
			return ;
		}
		
		if( !$aExtMeta = $aExtMgr->extensionMetainfo($sExtName) )
		{
			throw new Exception("启用扩展失败，指定的扩展尚未安装：%s",$sExtName) ;
		}
		
		// 检查依赖关系
		$this->checkDependence($aExtMeta,true) ;
		
		// 设置 setting
		$arrEnable = Setting::singleton()->item('/extensions','enable') ;
		$arrEnable[3][] = $sExtName ;
		Setting::singleton()->setItem('/extensions','enable',$arrEnable) ;
		
		// 执行 setup
		$sSetupClassName = $aExtMeta ->dataSetupClass() ;
		if(is_string($sSetupClassName)){
			$aSetup = new $sSetupClassName ;
			return $aSetup->install($aMessageQueue,$aExtMeta);
		}
	}
	
	public function disable($sExtName)
	{
		
	}
	
	/**
	 *  @param $bEnable bool 安装时为false,激活时为true
	 */
	public function checkDependence(ExtensionMetainfo $aExtMeta,$bEnable)
	{
		if( $aDenpendence = $aExtMeta->dependence() )
		{
			$aDenpendence->check(Platform::singleton(),$bEnable) ;
		}
	}
	
	//////////////////////////
	
	private function loadClassPackages(ExtensionMetainfo $aExtMeta)
	{
	
	}
	private function unloadClassPackages(ExtensionMetainfo $aExtMeta)
	{
		
	}
	
	/**
	 * 查看扩展数据的版本（包括已卸载扩展保留在系统中的数据）
	 */
	private function dataVersion()
	{
		return null ;
	}
	
	/**
	 * 升级扩展的资源版本
	 */
	private function upgradeData(ExtensionMetainfo $aExtMeta)
	{
		
	}
	
	/**
	 * 安装资源
	 */
	private function installData(ExtensionMetainfo $aExtMeta)
	{
		$sDataSetupClass = $aExtMeta->dataSetupClass() ;
		
	}
	
}

?>
