<?php
namespace org\opencomb\platform\system ;

use org\opencomb\platform\ext\ExtensionManager;

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
		if($this->arrSystemObjects)
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
		
		if( !$this->arrSystemObjects or !in_array($aObject,$this->arrSystemObjects,true) or $flyweightKey!=$flyweightKey and $sClass!=$sClass )
		{
			$this->arrSystemObjects[] = array(
					$aObject, $sClass, &$flyweightKey
			) ;
		}
		
		$arrInfo = array($sClass, $flyweightKey) ;
		if( !$this->arrInstanceInfos or !in_array($arrInfo,$this->arrInstanceInfos) )
		{
			$this->arrInstanceInfos[] = $arrInfo ;
		}
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
		
		// 缓存对像
		foreach($this->arrSystemObjects as $key=>$arrObjectInfo)
		{
			list($aObject, $sClass, $flyweightKey) = $arrObjectInfo ;
			$aCache->setItem($this->cacheStorePath($sClass,$flyweightKey),$aObject) ;
			
			unset($this->arrSystemObjects[$key]) ;
		}
		
		// 保存对像信息
		$aCache->setItem($this->cacheStorePath('platform-serialize-info',null),$this->arrInstanceInfos) ;
		
		// 保存 platform 的 publicFolder 对像
		$aCache->setItem($this->cacheStorePath("org\\jecat\\framework\\fs\\FileSystem",'public-folder'),$this->aPlatform->publicFolders()) ;
				
		// 还原 platform 
		Platform::switchSingleton($aOriPlatform) ;
	}
	
	public function restore()
	{
		$aOriPlatform = Platform::switchSingleton($this->aPlatform) ;
		
		$arrShareObjectsMemento = Object::shareObjectMemento() ;
		
		$aCache = $this->aPlatform->cache() ;
		
		// 恢复对像信息
		if( !$this->arrInstanceInfos=$aCache->item($this->cacheStorePath('platform-serialize-info',null),array()) )
		{
			// 还原 platform 
			Platform::switchSingleton($aOriPlatform) ;
			Object::setShareObjectMemento($arrShareObjectsMemento) ;
			
			return false ;
		}
		
		// ------------------------------------
		// 恢复各个对像
		foreach($this->arrInstanceInfos as &$arrInfo)
		{
			list($sClass,$flyweightKey) = $arrInfo ;
			
			$aInstance = $aCache->item( $this->cacheStorePath($sClass) ) ;
			if( !$aInstance or !($aInstance instanceof Object) )
			{
				// 还原 platform 
				Platform::switchSingleton($aOriPlatform) ;
				Object::setShareObjectMemento($arrShareObjectsMemento) ;
				
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
			Object::setShareObjectMemento($arrShareObjectsMemento) ;
			
			return false ;
		}
			
		// 设置 public folder
		$this->aPlatform->setPublicFolders($aPublicFolders) ;
		
		// 更新 AOP 缓存
		$a = AOP::singleton() ;
		if( $this->aPlatform->isDebugging() )
		{
			if( !AOP::singleton()->isValid() )
			{
				// 还原 platform 
				Platform::switchSingleton($aOriPlatform) ;
				Object::setShareObjectMemento($arrShareObjectsMemento) ;
				
				return false ;
			}
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
	
	private $arrInstanceInfos = array() ;
}
