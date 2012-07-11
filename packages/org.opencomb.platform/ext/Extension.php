<?php
namespace org\opencomb\platform\ext ;

use org\jecat\framework\cache\FSCache;

use org\jecat\framework\util\EventManager;

use org\jecat\framework\ui\xhtml\weave\WeaveManager;

use org\jecat\framework\lang\aop\AOP;

use org\jecat\framework\util\Version;
use org\jecat\framework\cache\EmptyCache;
use org\opencomb\platform\ext\ExtensionMetainfo;
use org\jecat\framework\system\Application;
use org\opencomb\platform\service\Service;
use org\jecat\framework\lang\Object;
use org\jecat\framework\fs\Folder;

/**
 * @wiki /蜂巢/扩展
 * 
 * ===扩展的元信息(metainfo)===
 * org\opencomb\platform\ext\ExtensionMetainfo 类负责维护和提供扩展固有的信息，\
 * 包括扩展的名称、说明，提供的类包、文件、模板目录等资源，以及扩展所有的能够向蜂巢平台提供的功能和数据。
 * 如果你需要了解一个扩展项蜂巢提供了哪些功能和数据，你可以访问 ExtensionMetainfo 类的相关方法。
 * 
 * ===扩展实例===
 * org\opencomb\platform\ext\Extension 类负责维护和提供一个扩展，在系统运行时的状态和信息。\
 * 包括扩展的缓存目录、临时目录、数据名录、配置信息对象，等等。\
 * 也包括扩展在载入和唤醒时所需要执行的函数。
 * 
 * ==扩展实例的享元对象==
 * 系统在初始化时，会为每个被激活的扩展创建一个 org\opencomb\platform\ext\Extension类(或子类)的享元实例。\
 * 当你需要访问一个扩展的相关信息和状态时，可以通过Extension::flyweight('extension name') 静态方法取得这个扩展的享元实例。[see /模式/单例和享元]
 * 
 * ===(^)比较：扩展的元信息（ExtensionMetainfo类）和 扩展实例（Extension类） ===
 * 扩展实例（Extension类对象）维护扩展在平台系统运行时的状态和信息，在扩展尚未安装之前无法获得有效的 Extension 对象。
 * 扩展的元信息（ExtensionMetainfo类）维护扩展固有的信息，ExtensionMetainfo对象中的内容不会随着系统运行发生变化；\
 * 并且，即使没有安装该扩展，也可以根据扩展提供的metainfo.xml文件，创建一个 ExtensionMetainfo 对象。
 * 
 * ===扩展的载入（load）===
 * [todo]
 * 
 * ===扩展的唤醒（weekup）===
 * [todo]
 * 
 * ===(^)比较：扩展的载入（load）和 扩展的唤醒（weekup）===
 * [todo]
 * 
 */
class Extension extends Object 
{
	/**
	 * @return Extension
	 */
	static public function flyweight($sExtensionName,$bAutoCreate=false,$sClassName=null)
	{
		return ExtensionManager::singleton()->extension($sExtensionName) ;
	}
	
	public function __construct(ExtensionMetainfo $aMeta)
	{
		$this->aMetainfo = $aMeta ;
	}

	/**
	 * @wiki /配置/蜂巢扩展的配置信息
	 * 
	 * 每个扩展的配置信息都是独立保存的，通过 $aSetting = Extension::flyweight('xxx')->setting() 可以取得名为xxx的扩展专有的Setting 对象。\
	 * Extension::flyweight('xxx') 返回的是一个 Extension 类的享元实例，系统会为每个被激活的扩展创建一个 Extension 对象，该对象负责维护和提供对应扩展的所有信息和数据。[see /蜂巢/扩展#扩展实例的享元对象]
	 * setting() 方法返回所属扩展的专有的Setting对象。在这个 $aSetting 对象中，只包含了对应扩展的配置。
	 * 在开发扩展时，每个扩展通常只需要在本扩展的配置对象中存取信息。
	 * 
	 * [!]蜂巢有计划在以后的版本中，实现扩展数据的保护机制，只有在授权的情况下，一个扩展才能够访问其他扩展的配置信息。[/!]
	 * 
	 * = (^)比较：蜂巢平台 和 蜂巢扩展 的配置信息 =
	 * 当你需要访问扩展的配置时，使用 Extentsion::setting() 方法，取得扩展的配置对象。(^)setting()是一个动态方法，所以需要先得到扩展对应的 Extension 对象，最简单的方式是 Extension 类的静态方法 flyweight('xxx') 。
	 * 当你需要访问蜂巢平台的配置时，使用 Setting::singleton() 返回整个系统的 Setting对象，它包含全系统的配置信息。(^)各个扩展的配置信息只是全系统配置树结构上的一个分支。
	 * 
	 * @return org\jecat\framework\setting\Setting
	 */
	public function setting()
	{
		return $this->aMetainfo->setting();
	}
	
	public function cache()
	{
		if(!$this->aCache)
		{
			$this->aCache = new FSCache(
					Folder::singleton()->findFolder('data/cache/'.$this->metainfo()->name(),Folder::FIND_AUTO_CREATE)->path()
			) ;
		}
		return $this->aCache ;
	}
	
	/**
	 * @return org\jecat\framework\fs\Folder
	 */
	public function filesFolder()
	{
		if( !$this->aFilesFolder )
		{
			$aServiceFilesFolder = Service::singleton()->filesFolder() ;
			$this->aFilesFolder = $aServiceFilesFolder->findFolder($this->metainfo()->name(),Folder::FIND_AUTO_CREATE) ;
			$this->aFilesFolder->setHttpUrl($aServiceFilesFolder->httpUrl().'/'.$this->metainfo()->name()) ;
		}
		return $this->aFilesFolder ;
	}
	
	/**
	 * @return org\jecat\framework\fs\Folder
	 */
	public function dataFolder()
	{
		if( !$this->aDataFolder )
		{
			$this->aDataFolder = Folder::singleton()->findFolder('data/extensions/'.$this->metainfo()->name(),Folder::FIND_AUTO_CREATE) ;
		}
		return $this->aDataFolder ;
	}
	
	/**
	 * @return org\jecat\framework\fs\Folder
	 */
	public function tmpFolder()
	{
		if( !$this->aTmpFolder )
		{
			$this->aTmpFolder = Folder::singleton()->findFolder('data/tmp/'.$this->metainfo()->name(),Folder::FIND_AUTO_CREATE) ;
		}
		return $this->aTmpFolder ;
	}
	
	private static function extensionFlyweightFolder($sType,$sSubPath,$sExtName)
	{
		$sFlyweightKey = 'oc-ext-'.$sType.'-'. $sExtName ;
		
		if( !$aFolder=Folder::flyweight($sFlyweightKey,false) )
		{
			$sPath = $sSubPath.$sExtName ;
			
			if($aFolder = Folder::singleton()->findFolder($sPath,Folder::FIND_AUTO_CREATE))
			{
				Folder::setFlyweight($aFolder,$sFlyweightKey) ;
			}
		}
		
		return $aFolder ;		
	}

	/**
	 * @return ExtensionMetainfo
	 */
	public function metainfo()
	{
		return $this->aMetainfo ;
	}
	
	public function initRegisterAspect(AOP $aAop)
	{}
	
	public function initRegisterEvent(EventManager $aEventMgr)
	{}
	
	public function initRegisterUITemplateWeave(WeaveManager $aWeave)
	{}
	
	public function load()
	{}
	
	public function active(Service $aService)
	{}
	
	public function setRuntimePriority($nPriority)
	{
		$this->nRuntimePriority = $nPriority ;
	}
	
	public function runtimePriority()
	{
		return $this->nRuntimePriority ;
	}
	
	/**
	 * ExtensionMetainfo::dataVersion() 返回的是扩展要求的数据版本
	 * Extension::dataVersion() 返回的是实际系统中使用的数据版本
	 * 
	 * @return org\jecat\framework\util\Version
	 */
	public function dataVersion()
	{
		if( !$this->aDataVersion )
		{
			if( $sDataVersion = $this->setting()->item('/','data-version') )
			{
				$this->aDataVersion = Version::fromString($sDataVersion) ;
			}
			else
			{
				$this->aDataVersion = $this->metainfo()->dataVersion() ;
			}
		}
		return $this->aDataVersion ;
	}
	
	static public function retraceExtensionName($arrStack=null)
	{
		if(!$arrStack)
		{
			$arrStack = debug_backtrace() ;
		}
		
		foreach($arrStack as $arrCall)
		{
			// todo ... 
			// 回头需要测试一下  preg_match 是否会效率更高一些
			if( !empty($arrCall['object']) )
			{
				$sClass = get_class($arrCall['object']) ;
				if( substr($sClass,0,7)=='org\\opencomb\\platform\\ext\\' and $nEndPos=strpos($sClass,'\\',7) )
				{
					return substr($sClass,7,$nEndPos-7) ;
				}
			}
		}
		
		return 'platform' ;
	}
	
	/**
	 * @return Extension
	 */
	static public function retraceExtension(ExtensionManager $aExtMgr=null)
	{
		if( !$sExtensionName = self::retraceExtensionName() )
		{
			return null ; 
		}
		
		if(!$aExtMgr)
		{
			$aExtMgr = Application::singleton()->extensions() ;
		}
		
		return $aExtMgr->extension($sExtensionName) ;
	}
	
	private $aMetainfo ;
	
	private $aCache ;
	private $aFilesFolder ;
	private $aDataFolder ;
	private $aTmpFolder ;
	private $aDataVersion ;
	
	private $nRuntimePriority = -1 ;
	
}



