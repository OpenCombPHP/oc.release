<?php
namespace org\opencomb\platform\ext ;

use org\opencomb\platform\service\Service;
use org\opencomb\platform\service\ServiceSerializer;
use org\jecat\framework\message\Message;
use org\jecat\framework\lang\Type;
use org\jecat\framework\setting\Setting;
use org\jecat\framework\fs\Folder;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\lang\Object;
use org\jecat\framework\message\MessageQueue;
use org\jecat\framework\lang\oop\ClassLoader;
use org\opencomb\platform\ext\dependence\RequireItem;
use org\opencomb\platform as oc;
use org\jecat\framework\util\Version;

class ExtensionSetup extends Object
{
	public function install(Folder $aExtensionFolder,MessageQueue $aMessageQueue)
	{
		$aExtMgr = ExtensionManager::singleton() ;
		
		// 读取扩展的 metainfo 
		$aExtMeta = ExtensionMetainfo::load($aExtensionFolder) ;
		
		// 检查扩展是否已经安装
		// 名称和版本都一致才算重复
		if( $aInstalled=$aExtMgr->extensionMetainfo( $aExtMeta->name() ) and $aInstalled->version()->compare( $aExtMeta->version() ) ===0 )
		{
			throw new Exception(
				"安装扩展操作退出，无法重复安装扩展：%s(%s : %s)",
				array(
					$aExtMeta->title(),
					$aExtMeta->name(),
					$aExtMeta->version()
				)
			) ;
		}
		
		// 检查依赖关系
		$this->checkDependence($aExtMeta,false) ;
		
		// 加载新扩展的类包
		$this->loadClassPackages($aExtMeta) ;
		
		// 检查系统中是否保留扩展的数据
		if( $sDataVersion=Setting::singleton()->item('/extensions/'.$aExtMeta->name(),'data-version') )	// 已经安装同名扩展，或系统中保留此扩展数据
		{
			// 升级数据
			$this->upgradeData(Version::fromString($sDataVersion),$aExtMeta , $aMessageQueue) ;
		}
		
		// 安装资源
		else
		{
			$this->installData($aExtMeta,$aMessageQueue) ;
		}
		
		// 写入数据版本
		$aExtMeta->setting()->setItem('/','data-version',$aExtMeta->dataVersion()->toString(false)) ;
		
		// 添加扩展的安装信息
		// 已经存在相同扩展，则是替换
		$aExtMgr->setInstalledExtension($aExtMeta) ;
		
		ServiceSerializer::singleton()->addSystemObject($aExtMgr) ;
		
		// 设置 setting
		$arrExtList = $this->buildInstalledExtensionList($aExtMgr) ;
		Setting::singleton()->setItem('/extensions','installeds',$arrExtList) ;
		
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
		
		// 刷新系统缓存
		ServiceSerializer::singleton()->addSystemObject($aExtMgr) ;
	}
	
	const TYPE_KEEP = 'keep';
	const TYPE_REMOVE = 'remove';
	
	public function uninstall($sExtName,MessageQueue $aMessageQueue,$bRetainData=true)
	{
		$aExtensionManager = ExtensionManager::singleton();
		if( !$aExtMeta = $aExtensionManager->extensionMetainfo($sExtName) )
		{
			return false ;
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
		if(!empty($arrDependence))
		{
			$aMessageQueue->create(Message::success,'无法卸载扩展 `%s` ，它被 %s 依赖',array($sExtName,implode(' , ',array_keys($arrDependence) ),)) ;
			return false ;
		}
		
		
		// 先禁用 扩展 ------------
		$this->disable($sExtName) ;

		
		// 处理扩展数据 ------------
		// 记录扩展的数据版本
		if($bRetainData)
		{
			$aExtension = new Extension($aExtMeta) ;
			$aExtension->setting()->setItem('/','data-version',$aExtension->dataVersion()->toString(false)) ;
		}
		// 清理数据
		else
		{
			ExtensionDataClearer::singleton()->clear(Service::singleton(),$sExtName,$aMessageQueue) ;
		}
		
		
		// 删除扩展 ------------
		
		// 修改 ExtensionManager
		$aExtensionManager->removeInstallExtension($aExtMeta);
		ServiceSerializer::singleton()->addSystemObject($aExtensionManager) ;

		// 设置 setting	
		$aExtList = $this->buildInstalledExtensionList($aExtensionManager) ;
		Setting::singleton()->setItem('/extensions','installeds',$aExtList) ;

		$aMessageQueue->create(Message::success,'扩展`%s`已经从服务中卸载',array($sExtName)) ;
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

		// -- Extension Enable --------
		// 设置 setting
		$arrEnable = Setting::singleton()->item('/extensions','enable') ;
		foreach($arrEnable as &$arrExtNameList)
		{
			if(($pos = array_search($sExtName,$arrExtNameList))!==false)
			{
				unset($arrExtNameList[$pos]) ;
			}
		}
		Setting::singleton()->setItem('/extensions','enable',$arrEnable) ;

		// 修改 ExtensionManager
		$aExtensionManager->removeEnableExtension($aExtMeta);

		// 刷新系统缓存
		ServiceSerializer::singleton()->addSystemObject($aExtensionManager) ;
	}
	
	public function changePriority($sExtName,$nNewPriority){
		$aExtMgr = ExtensionManager::singleton() ;
		
		if( !$aExtMeta = $aExtMgr->extensionMetainfo($sExtName) )
		{
			throw new Exception("修改扩展优先级失败，指定的扩展尚未安装：%s",$sExtName) ;
		}
		
		// int param priority
		$nNewPriority = (int)$nNewPriority;
		
		// check dependence
		$arrDependence = array();
		
		foreach($aExtMeta->dependence()->iterator() as $aRequireItem){
			if($aRequireItem->type() === RequireItem::TYPE_EXTENSION){
				$sDepExtName = $aRequireItem->itemName() ;
				
				if(!$aDepExtMeta = $aExtMgr->extensionMetainfo($sDepExtName)){
					throw new Exception("依赖的扩展尚未安装：%s",$sDepExtName) ;
				}
				
				// 优先级不能小于它依赖的扩展
				if( $nNewPriority < $aDepExtMeta->priority() ){
					throw new Exception('优先级不能小于依赖的扩展: %s ( %d )',array($sDepExtName,$aDepExtMeta->priority() ) );
				}
			}
		}
		
		// 设置 setting
		$aSetting = Setting::singleton() ;
		$arrEnable = $aSetting->item('/extensions','enable') ;
		$arrEnableNew = array();
		foreach($arrEnable as $nPriority=>$arrExtNameList){
			
			$arrEnableNew[$nPriority] = array();
			
			// 从它原来的优先级删除
			foreach($arrExtNameList as $sEnableExtName){
				if($sEnableExtName !== $sExtName){
					$arrEnableNew[$nPriority][] = $sEnableExtName;
				}
			}
			
			// 添加到新的优先级
			if( $nPriority == $nNewPriority ){
				$arrEnableNew[$nPriority][] = $sExtName;
			}
		}
		// 如果新的优先级不存在，需要额外添加
		if( !isset($arrEnableNew[$nNewPriority]) ){
			$arrEnableNew[$nNewPriority] = array($sExtName);
		}
		$aSetting->setItem('/extensions','enable',$arrEnableNew) ;
		
		// 刷新系统缓存
		ServiceSerializer::singleton()->addSystemObject($aExtMgr) ;
	}
	
	const TYPE_DIRE_UP = 'up' ;
	const TYPE_DIRE_DOWN = 'down' ;
	public function changeOrder($sExtName,$sDire){
		$aSetting = Setting::singleton() ;
		
		// 读取并整理顺序
		$arrEnable = $aSetting->item('/extensions','enable');
		
		$nExtPriority = -1 ; // $sExtName 的优先级
		$nExtPosition = -1 ; // $sExtName 目前的位置
		
		// 查找并确定位置
		foreach($arrEnable as $nPriority => $arrExtList){
			foreach($arrExtList as $nPos => $sEnableExtName){
				if($sExtName === $sEnableExtName){
					$nExtPriority = (int) $nPriority ;
					$nExtPosition = (int) $nPos ;
					break;
				}
			}
			
			// 已经找到，可以直接退出了
			if( -1 !== $nExtPriority or -1 !== $nExtPosition ){
				break;
			}
		}
		
		// 没找到
		if( -1 === $nExtPriority or -1 === $nExtPosition ){
			throw new Exception('未找到扩展 `%s`，可能是尚未安装或尚未激活',$sExtName);
			return false;
		}
		
		// 检查依赖关系
		function isDependence($sExtFrom , $sExtTo){
			$aExtMgr = ExtensionManager::singleton() ;
		
			if( !$aExtMeta = $aExtMgr->extensionMetainfo($sExtFrom) )
			{
				throw new Exception("指定的扩展尚未安装：%s",$sExtFrom) ;
			}
			
			foreach($aExtMeta->dependence()->iterator() as $aRequireItem){
				if($aRequireItem->type() === RequireItem::TYPE_EXTENSION){
					$sDepExtName = $aRequireItem->itemName() ;
				
					if($sDepExtName === $sExtTo){
						return true;
					}
				}
			}
			return false;
		}
		// 分析参数并做相应处理
		switch($sDire){
		case self::TYPE_DIRE_UP:
			if( 0 === $nExtPosition ){
				throw new Exception('已经是最前面，不能再往前了');
				return false;
			}
			
			// 它将交换的目标
			$sMoveExtName = $arrEnable[$nExtPriority][$nExtPosition-1] ;
			
			// 检查依赖关系
			if(isDependence($sExtName , $sMoveExtName)){
				throw new Exception('不能移动到它依赖的扩展之前');
				return false;
			}
			
			// 修改
			$arrEnable[$nExtPriority][$nExtPosition] = $sMoveExtName ;
			$arrEnable[$nExtPriority][$nExtPosition-1] = $sExtName ;
			break;
		case self::TYPE_DIRE_DOWN:
			if( $nExtPosition >= count($arrEnable[$nExtPriority]) -1 ){
				throw new Exception('已经是最后面，不能再往后了');
				return false;
			}
			
			// 它将交换的目标
			$sMoveExtName = $arrEnable[$nExtPriority][$nExtPosition+1] ;
			
			// 检查依赖关系
			if(isDependence($sMoveExtName , $sExtName)){
				throw new Exception('不能移动到依赖它的扩展之后');
				return false;
			}
			
			// 修改
			$arrEnable[$nExtPriority][$nExtPosition] = $sMoveExtName ;
			$arrEnable[$nExtPriority][$nExtPosition+1] = $sExtName ;
			break;
		default:
			throw new Exception('未知参数dire : `%s`',$sDire );
			return false;
			break;
		}
		
		// 保存setting
		$aSetting->setItem('/extensions','enable',$arrEnable) ;
	}
	
	/**
	 *  @param $bEnable bool 安装时为false,激活时为true
	 */
	public function checkDependence(ExtensionMetainfo $aExtMeta,$bEnable)
	{
		if( $aDenpendence = $aExtMeta->dependence() )
		{
			$aDenpendence->check(Service::singleton(),$bEnable) ;
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
		$aExtFolder = new Folder($aExtMeta->installPath());
		foreach( $aExtMeta->packageIterator() as $package){
			// $package[0] 是 namespace
			// $package[1] 是 文件夹，从$aExtMeta->installPath()算起
			$sSourceFolder = $aExtFolder->path().$package[1];
			if(in_array($sSourceFolder,$arrClassLoaderPackagePath)){
				continue;
			}
			$this->arrLoadedClassPackages[] = $aClassLoader->addPackage($package[0],new Folder($sSourceFolder));
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
	private function upgradeData(Version $aFromVersion , ExtensionMetainfo $aExtMeta , MessageQueue $aMessageQueue)
	{
		$arrEdge = array();
		$arrVersion = array();
		foreach($aExtMeta->dataUpgradeClassIterator() as $aDataUpgradeClass){
			$arrEdge[ $aDataUpgradeClass['class' ] ] = array(
				'from' => $aDataUpgradeClass['from']->toString(),
				'to' => $aDataUpgradeClass['to']->toString(),
			);
			$arrVersion[ $aDataUpgradeClass['from']->toString() ] = $aDataUpgradeClass['from'];
			$arrVersion[ $aDataUpgradeClass['to']->toString() ] = $aDataUpgradeClass['to'];
		}
		
		// 借用平台升级计算路径的类来计算路径
		$aCalcPath = new \org\opencomb\platform\service\upgrader\CalcPath ;
		$arrPath = $aCalcPath->calc( $arrEdge , $aFromVersion->toString() , $aExtMeta->version()->toString() );
		
		if($arrPath){
			foreach($arrPath as $sDataUpgraderClass ){
				if(!Type::hasImplements($sDataUpgraderClass,'org\\opencomb\\platform\\ext\\IExtensionDataUpgrader'))
				{
					throw new Exception(
						'扩展%s提供的数据升级程序没有实现 org\\opencomb\\platform\\ext\\IExtensionDataUpgrader 接口，无法执行数据升级操作',
						$aExtMeta->name()
					);
					return ;
				}
			
				$aUpgrader = new $sDataUpgraderClass ;
				try{
					$aUpgrader->upgrade($aMessageQueue, $arrVersion[ $arrEdge[$sDataUpgraderClass]['from'] ],$aExtMeta) ;
					$aMessageQueue->create(
						Message::success,
						"成功将扩展%s的数据从`%s`升级到`%s`",
						array(
							$aExtMeta->name(),
							$arrEdge[$sDataUpgraderClass]['from'],
							$arrEdge[$sDataUpgraderClass]['to'],
						)
					) ;
				} catch (\Extension $e) {
					$aMessageQueue->create(Message::error,"安装扩展%s的数据时遇到了错误。",$aExtMeta->name()) ;
				}
			}
		}else{
			throw new Exception(
				'扩展%s未提供从版本`%s`至版本`%s`的数据升级程序',
				array(
					$aExtMeta->name(),
					$aFromVersion->toString(),
					$aExtMeta->version()->toString()
				)
			);
		}
	}
	
	/**
	 * 安装资源
	 */
	private function installData(ExtensionMetainfo $aExtMeta , MessageQueue $aMessageQueue)
	{
		if( !$sDataInstallerClass = $aExtMeta->dataInstallerClass())
		{
			return ;
		}
		
		if(!Type::hasImplements($sDataInstallerClass,'org\\opencomb\\platform\\ext\\IExtensionDataInstaller'))
		{
			throw new Exception(
				'扩展%s提供的数据安装程序没有实现 org\\opencomb\\platform\\ext\\IExtensionDataInstaller 接口，无法执行数据按照操作',
				$aExtMeta->name()
			);
			return ;
		}
			
		$aInstaller = new $sDataInstallerClass ;
		try{
			$aInstaller->install($aMessageQueue,$aExtMeta) ;	
		} catch (\Extension $e) {
			$aMessageQueue->create(Message::error,"安装扩展%s的数据时遇到了错误。",$aExtMeta->name()) ;
		}
	}
	
	
	private function buildInstalledExtensionList(ExtensionManager $aExtMgr)
	{
		$arrList = array() ;
		foreach( $aExtMgr->metainfoIterator() as $aMetainfo )
		{
			// 优先使用相对路径
			$arrList[] = Folder::relativePath(oc\EXTENSIONS_FOLDER,$aMetainfo->installPath()) ?: $aMetainfo->installPath() ;
		}
		return $arrList ;
	}
}

