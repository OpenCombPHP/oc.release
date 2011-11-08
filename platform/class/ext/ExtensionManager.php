<?php
namespace oc\ext ;

use jc\util\VersionExcetion;

use jc\util\Version;

use jc\lang\Exception;
use jc\setting\Setting;
use oc\Platform;
use jc\lang\Object;

class ExtensionManager extends Object
{
	public function __construct(Setting $aSetting)
	{
		$this->arrInstalledExtensions = array() ;
		
		foreach( $aSetting->item("/extensions",'installeds')?: array()  as $sExtPath )
		{		
			$this->arrInstalledExtensions[] = ExtensionMetainfo::load($sExtPath) ;
		}
		
		$this->aSetting = $aSetting ;
	}
	
	public function installExtension()
	{
		// todo
	}
	
	public function loadExtension($sName)
	{
		if( !isset($this->arrInstalledExtensions[$sName]) )
		{
			throw new Exception("扩展:%s 尚未安装",$sName) ;
		}
		
		
	}
	
	/**
	 * \Iterator
	 */
	public function metainfoIterator()
	{
	}
	
	/**
	 * \Iterator
	 */
	public function iterator()
	{
		return new \ArrayIterator($this->arrExtensionInstances) ;
	}
	
	public function extension($sName) 
	{
		return isset($this->arrExtensionInstances[$sName])? $this->arrExtensionInstances[$sName]: null ;
	}
	
	public function add(Extension $aExt)
	{
		$this->arrExtensionInstances[$aExt->metainfo()->name()] = $aExt ;
	}
		
	private $arrInstalledExtensions = array() ;
		
	private $arrExtensionInstances = array() ;
	
	private $aSetting ;
}

?>