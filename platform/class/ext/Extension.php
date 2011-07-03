<?php
namespace oc\ext ;

use jc\mvc\view\htmlresrc\HtmlResourcePoolFactory;
use jc\lang\Object;

abstract class Extension extends Object 
{
	public function __construct(ExtensionMetainfo $aMeta)
	{
		$this->aMetainfo = $aMeta ;
	}

	public function metainfo()
	{
		return $this->aMetainfo ;
	}
	
	abstract public function load() ;
	
	static public function retraceExtensionName()
	{
		$arrStack = debug_backtrace() ;
		
		foreach($arrStack as $arrCall)
		{
			// todo ... 
			// 回头需要测试一下  preg_match 是否会效率更高一些
			if( !empty($arrCall['class']) and substr($arrCall['class'],0,7)=='oc\\ext\\' and $nEndPos=strpos($arrCall['class'],'\\',7) )
			{
				return substr($arrCall['class'],7,$nEndPos-7) ;
			}
		}
		
		return null ;
	}
	
	private $aMetainfo ;
}

?>