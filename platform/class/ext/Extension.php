<?php
namespace oc\ext ;

use jc\mvc\view\htmlresrc\HtmlResourcePoolFactory;
use jc\ui\xhtml\Factory as UIFactory ;
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
	
	private $aMetainfo ;
}

?>