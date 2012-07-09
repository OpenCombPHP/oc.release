<?php
namespace org\opencomb\platform\ext\dependence ;

use org\opencomb\platform\Platform;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\util\VersionScope;
use org\jecat\framework\util\Version;
use org\jecat\framework\util\VersionCompat;
use org\opencomb\platform\service\Service;

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
	
	public function __construct($sType,$sItemName,VersionScope $aVersionScope)
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
		$this->aRequireVersionScope = $aVersionScope ;
	}
	
	public function setVersionScope(VersionScope $aVersionScope)
	{
		$this->aRequireVersionScope = $aVersionScope ;
	}
	
	/**
	 *  @param $bEnable bool 安装时为false,激活时为true
	 */
	public function check(Service $aService,$bExtensionEnabled)
	{
		switch ($this->sType)
		{
			case self::TYPE_LANGUAGE :
				return $this->checkLanguageVersion();
				break ;
			case self::TYPE_LANGUAGE_MODULE :
				return $this->checkLanguageModuleVersion();
				break ;
			case self::TYPE_FRAMEWORK :
				return $this->checkVersion(Version::FromString(\org\jecat\framework\VERSION)) ;
				break ;
			case self::TYPE_PLATFORM :
				return $this->checkVersion(Platform::singleton()->version()) ;
				break;
			case self::TYPE_EXTENSION :
				// 激活时为 true
				if($bExtensionEnabled){
					foreach($aService->extensions()->iterator() as $aExtension){
						if($aExtension->metainfo()->name() === $this->itemName()){
							return $this->checkVersion( $aExtension->metainfo()->versionCompat() );
						}
					}
					throw new Exception('依赖扩展 `%s` 未安装或未激活',$this->itemName() );
					return false;
				}
				// 安装时为 false
				else{
					$aExtMeta = $aService->extensions()->extensionMetainfo($this->itemName()) ;
					if(!$aExtMeta)
					{
						throw new Exception('依赖扩展 `%s` 未安装',$this->itemName() );
						return false ;
					}
					return $this->checkVersion( $aExtMeta->versionCompat() ) ;
				}
		}
	}
	
	public function checkVersion($aVersion)
	{
		if($aVersion instanceof VersionCompat){
			if(!$aVersion->check($this->aRequireVersionScope)){
				throw new Exception(
					'依赖关系%s:%s不满足，要求的版本为%s，提供的版本为%s',
					array(
						$this->type(),
						$this->itemName(),
						$this->aRequireVersionScope,
						$aVersion
					)
				);
				return false;
			}
		}else if($aVersion instanceof Version){
			if(!$this->aRequireVersionScope->isInScope($aVersion)){
				throw new Exception(
					'依赖关系%s:%s不满足，要求的版本为%s，提供的版本为%s',
					array(
						$this->type(),
						$this->itemName(),
						$this->aRequireVersionScope,
						$aVersion
					)
				);
				return false;
			}
		}
		return true;
	}
	
	public function itemName()
	{
		return $this->sItemName ;
	}
	public function type()
	{
		return $this->sType ;
	}
	
	public function versionScope(){
		return $this->aRequireVersionScope ;
	}
	
	private function checkLanguageVersion(){
		if($this->itemName() !== 'php'){
			throw new Exception(
				'依赖语言不满足，不支持`%s`语言 ',
				$this->itemName()
			);
			return false;
		}else{
			preg_match('|[\d\.]*|',phpversion(),$sPhpVersion);
			$aPhpVersion = Version::fromString($sPhpVersion[0]);
			if(!$this->aRequireVersionScope->isInScope($aPhpVersion)){
				throw new Exception(
					'依赖语言不满足，要求的版本为%s，提供的版本为%s',
					array(
						$this->itemName(),
						$this->aRequireVersionScope,
						$aPhpVersion
					)
				);
				return false;
			}
		}
		return true;
	}
	
	private function checkLanguageModuleVersion(){
		$sPhpVersion = phpversion($this->itemName());
		if(empty($sPhpVersion)){
			throw new Exception(
				'依赖语言模块不满足，%s不存在',
				$this->itemName()
			);
		}
		preg_match('|[\d\.]*|',$sPhpVersion,$arrMatch);
		$aPhpVersion = Version::fromString($arrMatch[0]);
		if(!$this->aRequireVersionScope->isInScope($aPhpVersion)){
			throw new Exception(
				'依赖语言模块不满足，要求的版本为%s，提供的版本为%s',
				array(
					$this->itemName(),
					$this->aRequireVersionScope,
					$aPhpVersion
				)
			);
			return false;
		}
		return true;
	}
	
	private $sType ;
	
	private $sItemName ;
	
	private $aRequireVersionScope ;
}

