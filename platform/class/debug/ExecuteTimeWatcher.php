<?php
namespace org\opencomb\platform\debug ;

use org\jecat\framework\mvc\controller\Response;
use org\jecat\framework\io\IOutputStream;
use org\jecat\framework\lang\Object;

class ExecuteTimeWatcher extends Object
{
	public function __construct()
	{}
	
	public function start($sWhat,$fTime=null)
	{
		$this->arrLogs[$sWhat]['start'] = $fTime===null? microtime(true): $fTime ;
	}
	
	public function finish($sWhat,$fTime=null)
	{
		$this->arrLogs[$sWhat]['finish'] = $fTime===null? microtime(true): $fTime ;
	}
	
	public function logs($bTree=false)
	{
		ksort($this->arrLogs) ;
		
		$arrLogs = array() ;
		foreach($this->arrLogs as $sWhat=>$arrLog)
		{
			if(!$bTree)
			{
				$arrLogs[$sWhat] = $arrLog['finish'] - $arrLog['start'] ;
			}
			else
			{
				$arrPath = explode('/',$sWhat) ;
				$arrNodeIdx =& $arrLogs ;
				foreach($arrPath as $idx=>&$sName)
				{
					$sName = trim($sName) ;
					if($sName)
					{
						if(!isset($arrNodeIdx[$sName]))
						{
							$arrNodeIdx[$sName] = array() ;
						}
						$arrNodeIdx =& $arrNodeIdx[$sName] ;
					}
				}
				
				$arrNodeIdx['*title'] = $sName ;
				$arrNodeIdx['*time'] = $arrLog['finish'] - $arrLog['start'] ;
			}
		}
		return $arrLogs ;
	}
	
	public function printLogs(IOutputStream $aStream=null)
	{
		if(!$aStream)
		{
			$aStream = Response::singleton()->printer() ;
		}
		$arrLogs = $this->logs(true) ;
		$this->_printLogs($arrLogs,$aStream) ;
	}
	public function _printLogs(& $arrLogs,IOutputStream $aStream,$sTitle=null)
	{
		if( $sTitle )
		{
			$aStream->write("<li>{$sTitle}") ;
			if( isset($arrLogs['*time']) )
			{
				$aStream->write(": <b>".sprintf('%.12f',$arrLogs['*time'])."</b> sec") ;
			}
		}
		
		$aStream->write("<ul style='margin-left:20px'>") ;
		
		foreach($arrLogs as $sKey=>&$arrChildLogs)
		{
			if( in_array($sKey,array('*title','*time')) )
			{
				continue ;
			}
			
			$this->_printLogs($arrChildLogs, $aStream, $sKey) ;
		}
		
		$aStream->write("</ul>") ;
		
		
		if( $sTitle )
		{
			$aStream->write("</li>") ;
		}
	}

	private $arrLogs = array() ;
}

?>