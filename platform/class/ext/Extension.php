<?php
namespace org\opencomb\ext ;

use org\jecat\framework\setting\Setting;
use org\opencomb\ext\ExtensionMetainfo;
use org\jecat\framework\system\Application;
use org\opencomb\Platform;
use org\jecat\framework\lang\Object;

class Extension extends Object 
{
	public function __construct(ExtensionMetainfo $aMeta)
	{
		$this->aMetainfo = $aMeta ;
	}

	public function setting()
	{
		return Setting::singleton()->separate('extensions/'.$this->aMetainfo->name()) ;
	}
	public function cache()
	{}
	/**
	 * @return org\jecat\framework\fs\IFolder
	 */
	public function publicFolder()
	{
		//IFolder
	}
	/**
	 * @return org\jecat\framework\fs\IFolder
	 */
	public function dataFolder()
	{}
	/**
	 * @return org\jecat\framework\fs\IFolder
	 */
	public function temporaryFolder()
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
	
	public function active(Platform $aPlatform)
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
				if( substr($sClass,0,7)=='org\\opencomb\\ext\\' and $nEndPos=strpos($sClass,'\\',7) )
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