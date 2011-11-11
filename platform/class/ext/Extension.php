<?php
namespace oc\ext ;

use oc\ext\ExtensionMetainfo;
use jc\system\Application;
use oc\Platform;
use jc\lang\Object;

class Extension extends Object 
{
	public function __construct(ExtensionMetainfo $aMeta)
	{
		$this->aMetainfo = $aMeta ;
	}

	public function url()
	{
		return $this->application()->extensionsUrl() . $this->metainfo()->installFolderPath() ;
	}
	public function publicFilesUrl()
	{}
	
	public function settings()
	{}
	public function publicFiles()
	{}
	public function dataFiles()
	{}
	public function cacheFiles()
	{}
	public function temporaryFiles()
	{}

	/**
	 * @return ExtensionMetainfo
	 */
	public function metainfo()
	{
		return $this->aMetainfo ;
	}
	
	public function load()
	{}
	
	
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
				if( substr($sClass,0,7)=='oc\\ext\\' and $nEndPos=strpos($sClass,'\\',7) )
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
}

?>