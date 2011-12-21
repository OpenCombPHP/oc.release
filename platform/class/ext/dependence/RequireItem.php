<?php
namespace org\opencomb\platform\ext\dependence ;

use org\jecat\framework\lang\Exception;
use org\jecat\framework\util\VersionScope;
use org\jecat\framework\util\Version;
use org\opencomb\platform\Platform;
use org\opencomb\platform\ext\ExtensionMetainfo;

class RequireItem
{
	const TYPE_LANGUAGE = 'language' ;
	const TYPE_LANGUAGE_MODULE = 'language_module' ;
	const TYPE_FRAMEWORK = 'framework' ;
	const TYPE_PLATFORM = 'platform' ;
	const TYPE_EXTENSION = 'extension' ;
	
	static private $arrTypes = array(
			self::TYPE_LANGUAGE ,
			self::TYPE_LANGUAGE_MODULE ,
			self::TYPE_FRAMEWORK ,
			self::TYPE_PLATFORM ,
			self::TYPE_EXTENSION ,
	) ;
	
	public function __construct($sType,$sItemName,$aVersionScope)
	{
		if( !in_array($sType,self::$arrTypes) )
		{
			throw new Exception("意外的依赖类型：%s",$sType) ;
		}
		if( in_array($sType,array(self::TYPE_EXTENSION,self::TYPE_LANGUAGE_MODULE)) and !$sItemName )
		{
			throw new Exception("当依赖类型为%s时，必须item属性",$sType) ;
		}
		
		$this->sType = $sType ;
		$this->sItemName = $sItemName ;
		$this->aVersionScope = $aVersionScope ;
	}
	
	public function setVersionScope(VersionScope $aVersionScope)
	{
		$this->aRequireVersionScope = $aVersionScope ;
	}

	public function check(Platform $aPlatform,$bExtensionEnabled)
	{
		switch ($this->sType)
		{
			case self::TYPE_LANGUAGE :
				break ;
			case self::TYPE_LANGUAGE_MODULE :
				break ;
			case self::TYPE_FRAMEWORK :
				return $this->checkVersion(Version::FromString(\org\jecat\framework\VERSION)) ;
				break ;
				
			case self::TYPE_PLATFORM :
				return $this->checkVersion($aPlatform->versionCompat()) ;
				
			case self::TYPE_EXTENSION :
				$aExtMeta = $aPlatform->extensions()->extensionMetainfo($this->itemName()) ;
				if(!$aExtMeta)
				{
					return false ;
				}
				return $this->checkVersion( $aExtMeta->versionCompat() ) ;
		}
	}
	
	public function checkVersion(VersionCompat $aVersionCompat)
	{
		
	}
	
	public function itemName()
	{
		return $this->sItemName ;
	}
	public function type()
	{
		return $this->sType ;
	}
	
	private $sType ;
	
	private $sItemName ;
	
	private $aRequireVersionScope ;
}

?>