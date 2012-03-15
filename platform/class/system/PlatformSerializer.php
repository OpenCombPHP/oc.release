<?php
namespace org\opencomb\platform\system ;

use org\jecat\framework\lang\aop\AOP;

use org\opencomb\platform\Platform;
use org\jecat\framework\cache\ICache;
use org\jecat\framework\lang\Object;

class PlatformSerializer extends Object
{
	public function __construct(Platform $aPlatform)
	{
		$this->aPlatform = $aPlatform ;
	}
	
	public function __destruct()
	{
		if($this->bNeedStore)
		{
			$this->store() ;
		}
	}
	
	public function addSystemObject(Object $aObject,$sClass=null,$flyweightKey=null)
	{
		if(!$sClass)
		{
			$sClass = get_class($aObject) ;
		}
		
		$this->arrSystemObjects[] = array(
				$aObject, $sClass, &$flyweightKey
		) ;

		$this->bNeedStore = true ;
	}
	
	public function addSystemSingletons()
	{
		$arrClasses = array(
				'org\\jecat\\framework\\lang\\oop\\ClassLoader' ,
				'org\\jecat\\framework\\system\\AccessRouter' ,
				'org\\jecat\\framework\\locale\\LocaleManager' ,
				'org\\jecat\\framework\\setting\\Setting' ,
				'org\\jecat\\framework\\ui\\SourceFileManager' ,
				'org\\jecat\\framework\\ui\\xhtml\\weave\\WeaveManager' ,
				'org\\jecat\\framework\\bean\\BeanFactory' ,
				'org\\jecat\\framework\\lang\\aop\\AOP' ,
				'org\\opencomb\\platform\\ext\\ExtensionManager' ,
		) ;
		
		foreach($arrClasses as $sClass)
		{
			$this->addSystemObject($sClass::singleton(),$sClass) ;
		}
	}
	
	public function store()
	{
		$aOriPlatform = Platform::switchSingleton($this->aPlatform) ;
		
		$aCache = $this->aPlatform->cache() ;
		
		$arrInstanceInfos = array() ;
		
		// 缓存对像
		foreach($this->arrSystemObjects as $arrObjectInfo)
		{
			list($aObject, $sClass, $flyweightKey) = $arrObjectInfo ;
			$aCache->setItem($this->cacheStorePath($sClass,$flyweightKey),$aObject) ;
			
			$arrInstanceInfos[] = array($sClass, $flyweightKey) ;
		}
		
		// 保存对像信息
		$aCache->setItem($this->cacheStorePath('platform-serialize-info',null),$arrInstanceInfos) ;
		
		// 保存 platform 的 publicFolder 对像
		$aCache->setItem($this->cacheStorePath("org\\jecat\\framework\\fs\\FileSystem",'public-folder'),$this->aPlatform->publicFolders()) ;
		
		$this->bNeedStore = false ;
		
		// 还原 platform 
		Platform::switchSingleton($aOriPlatform) ;
	}
	
	public function restore()
	{
		$aOriPlatform = Platform::switchSingleton($this->aPlatform) ;
		
		$aCache = $this->aPlatform->cache() ;
		
		// 恢复对像信息
		if( !$arrInstanceInfos=$aCache->item($this->cacheStorePath('platform-serialize-info',null)) )
		{
			// 还原 platform 
			Platform::switchSingleton($aOriPlatform) ;
			
			return false ;
		}
		
		// ------------------------------------
		// 恢复各个对像
		foreach($arrInstanceInfos as &$arrInfo)
		{
			list($sClass,$flyweightKey) = $arrInfo ;
			
			$aInstance = $aCache->item( $this->cacheStorePath($sClass) ) ;
			if( !$aInstance or !($aInstance instanceof Object) )
			{
				// 还原 platform 
				Platform::switchSingleton($aOriPlatform) ;
				
				return false ;
			}
		
			// set flyweight
			if($flyweightKey)
			{
				$sClass::setFlyweight($aInstance,$flyweightKey) ;
			}
			// set singleton
			else
			{
				$sClass::setSingleton($aInstance) ;
			}
		}
		
		// 恢复 public folder 对像
		$aPublicFolders = $aCache->item($this->cacheStorePath("org\\jecat\\framework\\fs\\FileSystem",'public-folder')) ;
		if( !$aPublicFolders or !($aPublicFolders instanceof Object) )
		{
			// 还原 platform 
			Platform::switchSingleton($aOriPlatform) ;
			
			return false ;
		}
			
		// 设置 public folder
		$this->aPlatform->setPublicFolders($aPublicFolders) ;
		
		// 更新 AOP 缓存
		if( $this->aPlatform->isDebugging() )
		{
			AOP::singleton()->refresh() ;
		}
			
		// 还原 platform 
		Platform::switchSingleton($aOriPlatform) ;
		
		return true ;
	}
	
	public function clearRestoreCache()
	{
		$this->aPlatform->cache()->delete('/system/objects') ;
	}
	
	public function cacheStorePath($sClass,$flyweightKey=null)
	{
		if($flyweightKey)
		{
			$flyweightKey = '-instances/'.implode('/',(array)$flyweightKey) ;
		}
		return "/system/objects/".str_replace('\\','.',$sClass).$flyweightKey ;
	}
	
	private $arrSystemObjects ;
	
	private $bNeedStore = false ;
}
