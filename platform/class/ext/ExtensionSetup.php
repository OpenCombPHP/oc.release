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
use org\opencomb\platform\ext\dependence\RequireItem ;
use org\jecat\framework\db\DB ;
use org\opencomb\platform\mvc\model\db\orm\Prototype ;

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
		
		// 修改 ExtensionManager
		$aExtMgr->addEnableExtension($aExtMeta);
	}
	
	const TYPE_KEEP = 'keep';
	const TYPE_REMOVE = 'remove';
	public function uninstall($sExtName , $sCode ,$sData)
	{
		$aExtensionManager = ExtensionManager::singleton();
		
		if( !$aExtMeta = $aExtensionManager->extensionMetainfo($sExtName) )
		{
			throw new Exception("卸载扩展失败，指定的扩展尚未安装：%s",$sExtName) ;
		}
		
		// check dependence
		$arrDependence = array();
		foreach($aExtensionManager->iterator() as $aExtension){
			foreach($aExtension->metainfo()->dependence()->iterator() as $aRequireItem){
				if($aRequireItem->type() === RequireItem::TYPE_EXTENSION){
					if($aRequireItem->itemName() === $sExtName){
						$arrDependence[$aExtension->metainfo()->name()] = $aExtension ;
					}
				}
			}
		}
		
		if(!empty($arrDependence)){
			throw new Exception(
				'无法卸载扩展 `%s` ，它被 %s 依赖',
				array(
					$sExtName,
					implode( ' , ' , array_keys($arrDependence) ),
				)
			);
			return FALSE;
		}
		
		// data
		switch($sData){
		case self::TYPE_KEEP:
			break;
		case self::TYPE_REMOVE:
			// 数据库
			
			// 防止在平台管理之外，数据库的结构发生改变
			Platform::singleton()->cache()->delete('/db');
			
			$arrTableList = array();
			$aDB = DB::singleton() ;
			$aReflecterFactory = $aDB->reflecterFactory() ;
			$strDBName = $aDB->driver(true)->currentDBName();
			$aDbReflecter = $aReflecterFactory->dbReflecter($strDBName);
			$sKey = 'Tables_in_'.$strDBName ;
			foreach( $aDbReflecter->tableNameIterator() as $value ){
				$tableName = $value[$sKey] ;
				if(Prototype::isExtensionTable($tableName,$sExtName)){
					$arrTableList [] = $tableName;
				}
			}
			
			foreach($arrTableList as $sTableName){
				$aDB->execute('DROP TABLE '.$sTableName);
			}
			
			// 数据库的结构已经改变，需要清理缓存
			Platform::singleton()->cache()->delete('/db');
			
			// settings
			$aExtension = $aExtensionManager->extension($sExtName);
			$aExtension->setting()->deleteKey('');
			
			// data
			$aExtension->publicFolder()->delete(true);
			break;
		default:
			throw new Exception(
				'sData 参数 错误 ： `%s`',
				array(
					$sData,
				)
			);
			break;
		}
		
		// code
		switch($sCode){
		case self::TYPE_KEEP:
			break;
		case self::TYPE_REMOVE:
			$aFolder = FileSystem::singleton()->findFolder($aExtMeta->installPath());
			if($aFolder){
				$aFolder->delete(true);
			}
			break;
		default:
			throw new Exception(
				'sCode 参数 错误 ： `%s`',
				array(
					$sCode,
				)
			);
			break;
		}
		
		// 设置 setting
		$arrEnable = Setting::singleton()->item('/extensions','enable') ;
		$arrEnable2 = array();
		foreach($arrEnable as $nPriority=>$arrExtNameList){
			$arrEnable2[$nPriority] = array();
			foreach($arrExtNameList as $sEnableExtName){
				if($sEnableExtName !== $sExtName){
					$arrEnable2[$nPriority][] = $sEnableExtName;
				}
			}
		}
		Setting::singleton()->setItem('/extensions','enable',$arrEnable2) ;
		
		$arrInstalled = Setting::singleton()->item('/extensions','installeds') ;
		$arrInstalled = array_diff($arrInstalled,array($aExtMeta->installPath()) ) ;
		Setting::singleton()->setItem('/extensions','installeds',$arrInstalled) ;
		
		// 修改 ExtensionManager
		$aExtensionManager->removeInstallExtension($aExtMeta);
	}
	
	public function disable($sExtName)
	{
		$aExtensionManager = ExtensionManager::singleton();
		
		if( !$aExtMeta = $aExtensionManager->extensionMetainfo($sExtName) )
		{
			throw new Exception("禁用扩展失败，指定的扩展尚未安装：%s",$sExtName) ;
		}
		
		// check dependence
		$arrDependence = array();
		foreach($aExtensionManager->iterator() as $aExtension){
			foreach($aExtension->metainfo()->dependence()->iterator() as $aRequireItem){
				if($aRequireItem->type() === RequireItem::TYPE_EXTENSION){
					if($aRequireItem->itemName() === $sExtName){
						$arrDependence[$aExtension->metainfo()->name()] = $aExtension ;
					}
				}
			}
		}
		
		// dependence exception
		if(!empty($arrDependence)){
			throw new Exception(
				'无法禁用扩展 `%s` ，它被 %s 依赖',
				array(
					$sExtName,
					implode( ' , ' , array_keys($arrDependence) ),
				)
			);
			return FALSE;
		}
		
		// 设置 setting
		$arrEnable = Setting::singleton()->item('/extensions','enable') ;
		$arrEnable2 = array();
		foreach($arrEnable as $nPriority=>$arrExtNameList){
			$arrEnable2[$nPriority] = array();
			foreach($arrExtNameList as $sEnableExtName){
				if($sEnableExtName !== $sExtName){
					$arrEnable2[$nPriority][] = $sEnableExtName;
				}
			}
		}
		Setting::singleton()->setItem('/extensions','enable',$arrEnable2) ;
		
		// 修改 ExtensionManager
		$aExtensionManager->removeEnableExtension($aExtMeta);
	}
	
	public function changePriority($sExtName,$nNewPriority){
		throw new Exception('unfinished function :%s',__METHOD__);
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
