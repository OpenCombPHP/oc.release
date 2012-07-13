<?php
namespace org\opencomb\platform ;

use org\jecat\framework\fs\Folder;
use org\jecat\framework\util\Version;
use org\jecat\framework\util\VersionCompat;
use org\opencomb\platform\service\ServiceFactory;

/**
 * @wiki /蜂巢/平台
 *
 * === 平台特点 ===
 *
 * == 扩展驱动模式 ==
 * 传统的互联网系统产品（例如：WorldPress, Discuz!）也支持插件机制，第三方开发者可以为系统提供插件，以增强系统的功能。\
 * 但是在技术实现上，却都无一例外地采用了“肥”主体系统，“瘦”插件模块的架构，功能和服务的实现主要依赖主系统，而主系统在设计风格上是“闭合”的，插件无法进入主系统以及影响主系统的行为。
 *
 * 因此，做为系统中的“二等公民”（甚至是“第三等公民”），第三方插件能够发挥的余地实际上是非常有限的。\
 * 这些产品的完善和市场成长，完全依赖主系统开发团队单方面的努力。
 *
 * 蜂巢平台采用了不同的架构：系统将所有的功能的实现交给了平台的“扩展”。\
 * 扩展成为了系统中的一等公民，平台不参与任何具体需求的实现。
 *
 * 第三方扩展向平台提供功能，整个系统能够向最终用户提供的互联网服务，完全取决于平台上所安装了哪些扩展。
 *
 * 并且，在安全机制的授权下，一个扩展可以全面地影响另一个扩展的行为：增加、修改，以及禁用其他扩展所提供的功能。
 *
 * 扩展的授权方式，由第三方开发者自由决定。所以有些扩展是开源和免费的，而有些扩展是被保护的，并且收取费用。
 *
 * 蜂巢平台的“扩展驱动模式”释放了第三方开发者的创造力，在这个开放的体系中，所有开发团队的资源都被整合在了一起。推动事业前进的，是利益一致的多方团队。
 *
 *
 *
 *
 */
class Platform
{
	const version = '0.4.0.1' ;
	const data_version = '0.3.1' ;
	const version_compat = "" ;
	
	/**
	 * @return Platform
	 */
	static public function singleton()
	{
		if(!self::$aGlobalInstance)
		{
			self::$aGlobalInstance = new self() ;
		}
		
		return self::$aGlobalInstance ;
	}


	/**
	 * @return org\jecat\framework\util\Version
	 */
	public function version($bString=false)
	{
		if($bString)
		{
			return self::version ;
		}
		else
		{
			if( !$this->aVersion )
			{
				$this->aVersion = Version::FromString(self::version) ;
			}
			return $this->aVersion ;
		}
	}
	/**
	 * @return org\jecat\framework\util\Version
	 */
	public function dataVersion($bString=false)
	{
		if($bString)
		{
			return self::data_version ;
		}
		else
		{
			if( !$this->aDataVersion )
			{
				$this->aDataVersion = Version::FromString(self::data_version) ;
			}
			return $this->aDataVersion ;
		}
	}
	
	/**
	 * @return org\jecat\framework\util\VersionCompat
	 */
	public function versionCompat()
	{
		if(!$this->aVersionCompat)
		{
			$this->aVersionCompat = new VersionCompat;
			// 当前版本
			$this->aVersionCompat->addCompatibleVersion( $this->version() ) ;
			
			// 其它兼容版本
			if( self::version_compat )
			{
				$this->aVersionCompat->addFromString(self::version_compat) ;
			}
		}
		return $this->aVersionCompat ;
	}
	
	/**
	 * @return org\jecat\framework\fs\Folder
	 */
	public function installFolder($bReturnPath=false)
	{
		return $bReturnPath? ROOT: $this->aInstallFolder ;
	}
	
	public function filesFolder()
	{
		if( !$this->aFilesFolder )
		{
			$this->aFilesFolder = new Folder(PUBLIC_FILES_FOLDER) ;
			$this->aFilesFolder->setHttpUrl(PUBLIC_FILES_URL) ;
		}
		return $this->aFilesFolder ;
	}
	
	// --------
	private function __construct()
	{
		$this->aInstallFolder = new Folder(ROOT) ;
	}
		
	static private $aGlobalInstance ; 
	
	private $aInstallFolder ;
	
	private $aVersion ;
	
	private $aVersionCompat ;
	
	private $aDataVersion;
	
	private $aFilesFolder ;
	
}



