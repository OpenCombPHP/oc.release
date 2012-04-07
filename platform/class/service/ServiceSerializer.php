<?php
namespace org\opencomb\platform\service ;

use org\jecat\framework\cache\Cache;
use org\jecat\framework\lang\aop\AOP;
use org\jecat\framework\lang\Object;

class ServiceSerializer extends Object
{
	public function __construct(Service $aService)
	{
		$this->aService = $aService ;
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
		$aOriService = Service::switchSingleton($this->aService) ;
		
		$aCache = Cache::singleton() ;
		
		// 缓存对像
		foreach($this->arrSystemObjects as $key=>$arrObjectInfo)
		{
			list($aObject, $sClass, $flyweightKey) = $arrObjectInfo ;
			$aCache->setItem($this->cacheStorePath($sClass,$flyweightKey),$aObject) ;
			
			unset($this->arrSystemObjects[$key]) ;
		}
		
		// 保存对像信息
		$aCache->setItem($this->cacheStorePath('service-serialize-info',null),$this->arrInstanceInfos) ;
		
		// 保存 Service 的 publicFolder 对像
		$aCache->setItem($this->cacheStorePath("org\\jecat\\framework\\fs\\FileSystem",'public-folder'),$this->aService->publicFolders()) ;
				
		// 还原 Service 
		Service::switchSingleton($aOriService) ;
	}
	
	public function restore()
	{
		$aOriService = Service::switchSingleton($this->aService) ;
		
		$arrShareObjectsMemento = Object::shareObjectMemento() ;
		
		$aCache = Cache::singleton() ;
		
		// 恢复对像信息
		if( !$this->arrInstanceInfos=$aCache->item($this->cacheStorePath('service-serialize-info',null),array()) )
		{
			// 还原 Service 
			Service::switchSingleton($aOriService) ;
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
				// 还原 Service 
				Service::switchSingleton($aOriService) ;
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
			// 还原 Service 
			Service::switchSingleton($aOriService) ;
			Object::setShareObjectMemento($arrShareObjectsMemento) ;
			
			return false ;
		}
			
		// 设置 public folder
		$this->aService->setPublicFolders($aPublicFolders) ;
		
		// 更新 AOP 缓存
		$a = AOP::singleton() ;
		if( $this->aService->isDebugging() )
		{
			if( !AOP::singleton()->isValid() )
			{
				// 还原 Service 
				Service::switchSingleton($aOriService) ;
				Object::setShareObjectMemento($arrShareObjectsMemento) ;
				
				return false ;
			}
		}
			
		// 还原 Service 
		Service::switchSingleton($aOriService) ;
		
		return true ;
	}
	
	public function clearRestoreCache()
	{
		Cache::singleton()->delete('/system/objects') ;
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

