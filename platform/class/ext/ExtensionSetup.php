<?php
namespace org\opencomb\platform\ext ;

use org\opencomb\platform\Platform;

use org\jecat\framework\setting\Setting;

use org\jecat\framework\fs\IFolder;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\lang\Object;
use org\jecat\framework\message\MessageQueue;
use org\jecat\framework\message\Message;
use org\jecat\framework\lang\oop\ClassLoader ;
use org\jecat\framework\fs\FileSystem;

class ExtensionSetup extends Object
{
	public function install(IFolder $aExtensionFolder , MessageQueue $aMessageQueue)
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
			$this->upgradeData($aExtMeta , $aMessageQueue) ;
		}
		
		// 安装资源
		else
		{
			$this->installData($aExtMeta , $aMessageQueue) ;
		}
		
		// 设置 setting
		$arrInstalled = Setting::singleton()->item('/extensions','installeds') ;
		$arrInstalled[] = $aExtensionFolder->path() ;
		Setting::singleton()->setItem('/extensions','installeds',$arrInstalled) ;
		
		// 添加扩展的安装信息
		$aExtMgr->addInstalledExtension($aExtMeta) ;
		
		// 卸载新扩展的类包
		$this->unloadClassPackages($aExtMeta) ;
		
		return $aExtMeta ;
	}
	
	public function enable($sExtName)
	{
		$aExtMgr = ExtensionManager::singleton() ;
		
		// 已经激活
		foreach($aExtMgr->enableExtensionNameIterator() as $enableExtensionName){
			if($enableExtensionName == $sExtName){
				throw new Exception("启用扩展失败，指定的扩展已经激活：%s",$sExtName) ;
				return ;
			}
		}
		
		if( !$aExtMeta = $aExtMgr->extensionMetainfo($sExtName) )
		{
			throw new Exception("启用扩展失败，指定的扩展尚未安装：%s",$sExtName) ;
		}
		
		// 检查依赖关系
		$this->checkDependence($aExtMeta,true) ;
		
		// 设置 setting
		$arrEnable = Setting::singleton()->item('/extensions','enable') ;
		$arrEnable[$aExtMeta->priority()][] = $sExtName ;
		Setting::singleton()->setItem('/extensions','enable',$arrEnable) ;
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
		$aClassLoader = ClassLoader::singleton();
		// ClassLoader 中已有的package的path
		$arrClassLoaderPackagePath = array();
		foreach($aClassLoader->packageIterator() as $package){
			$arrClassLoaderPackagePath [] = $package->folder()->path() ;
		}
		// 加载class
		$aExtFolder = FileSystem::singleton()->findFolder($aExtMeta->installPath());
		foreach( $aExtMeta->pakcageIterator() as $package){
			// $package[0] 是 namespace
			// $package[1] 是 文件夹，从$aExtMeta->installPath()算起
			$sSourceFolder = $aExtFolder->path().$package[1];
			if(in_array($sSourceFolder,$arrClassLoaderPackagePath)){
				continue;
			}
			$this->arrLoadedClassPackages[] = $aClassLoader->addPackage($package[0],$sSourceFolder);
		}
	}
	private function unloadClassPackages(ExtensionMetainfo $aExtMeta)
	{
		$aClassLoader = ClassLoader::singleton();
		foreach( $this->arrLoadedClassPackages as $package){
			$aClassLoader->removePackage($package);
		}
	}
	
	// for function loadClassPackages() and unloadClassPackages()
	private $arrLoadedClassPackages = array ();
	
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
	private function installData(ExtensionMetainfo $aExtMeta , MessageQueue $aMessageQueue)
	{
		$sDataSetupClass = $aExtMeta->dataSetupClass() ;
		if(is_string($sDataSetupClass)){
			$aSetup = new $sDataSetupClass ;
			$aSetup->install($aMessageQueue,$aExtMeta);
		}
	}
	
}

?>
