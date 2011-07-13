<?php
namespace oc\ext ;

use jc\lang\Object;

class ExtensionManager extends Object
{
	/**
	 * \Iterator
	 */
	public function iterator()
	{
		return new \ArrayIterator($this->arrExtensions) ;
	}
	
	public function extension($sName) 
	{
		return isset($this->arrExtensions[$sName])? $this->arrExtensions[$sName]: null ;
	}
	
	public function add(Extension $aExt)
	{
		$this->arrExtensions[$aExt->metainfo()->name()] = $aExt ;
	}
	
	private $arrExtensions = array() ;
}

?>