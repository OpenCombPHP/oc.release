<?php
namespace org\opencomb\platform\system ;

use org\opencomb\platform\Platform;
use org\jecat\framework\cache\ICache;
use org\jecat\framework\lang\Object;

class PlatformSerializer extends Object
{	
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
	
	public function addSystemObject(Object $aObject,$sClass=null,$flyweightKey=null)
	{
		if(!$sClass)
		{
			$sClass = get_class($aObject) ;
		}
		
		$this->arrSystemObjects[] = array(
				$aObject, $sClass, &$flyweightKey
		) ;
	}
	
	public function store(Platform $aPlatform,ICache $aCache)
	{
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
		$aCache->setItem($this->cacheStorePath("org\\jecat\\framework\\fs\\FileSystem",'public-folder'),$aPlatform->publicFolders()) ;
	}
	
	public function restore(Platform $aPlatform,ICache $aCache)
	{
		// 恢复对像信息
		if( !$arrInstanceInfos=$aCache->item($this->cacheStorePath('platform-serialize-info',null)) )
		{
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
			return false ;
		}
			
		// 设置 public folder
		$aPlatform->setPublicFolders($aPublicFolders) ;
	
		return true ;
	}
	
	public function clearRestoreCache(Platform $aPlatform)
	{
		$aPlatform->cache()->delete('/system/objects') ;
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
}
