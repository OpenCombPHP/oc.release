<?php
namespace org\opencomb\platform\debug ;

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
	
	public function logs()
	{
	}

	private $arrLogs = array() ;
}

?>