<?php
namespace org\opencomb\platform\ext ;

use org\opencomb\platform\ext\ExtensionManager;
use org\jecat\framework\db\ExecuteException;
use org\jecat\framework\util\VersionExcetion;
use org\jecat\framework\util\Version;
use org\jecat\framework\lang\Exception;
use org\jecat\framework\setting\Setting;
use org\opencomb\platform\Platform;
use org\jecat\framework\lang\Object;

class ExtensionManager extends Object implements \Serializable
{
	public function __construct(Setting $aSetting=null)
	{
		if(!$aSetting)
		{
			$aSetting = Setting::singleton() ;
		}
		
		$this->arrInstalledExtensions = array() ;
		
		foreach( $aSetting->item("/extensions",'installeds')?: array()  as $sExtPath )
		{		
			$aExtension = ExtensionMetainfo::load($sExtPath) ;
			$this->addInstalledExtension($aExtension) ;
		}
		
		$this->arrEnableExtensiongNames = $aSetting->item("/extensions",'enable') ?: array() ;
		
		$this->aSetting = $aSetting ;
	}
	
	/**
	 * @return ExtensionMetainfo
	 */
	public function extensionMetainfo($sName)
	{
		return isset($this->arrInstalledExtensions[$sName])? $this->arrInstalledExtensions[$sName]: null ;
	}

	/**
	 * \Iterator
	 */
	public function metainfoIterator()
	{
		return new \ArrayIterator($this->arrInstalledExtensions) ;
	}
	
	/**
	 * \Iterator
	 */
	public function extensionPriorities()
	{
		return array_keys($this->arrEnableExtensiongNames) ;
	}
	
	/**
	 * \Iterator
	 */
	public function enableExtensionNameIterator($nPriority=-1)
	{
		if($nPriority<0)
		{
			return new \ArrayIterator(
					call_user_func_array('array_merge',$this->arrEnableExtensiongNames)
			) ;
		}
		else 
		{
			return isset($this->arrEnableExtensiongNames[$nPriority])?
						new \ArrayIterator($this->arrEnableExtensiongNames[$nPriority]) :
						new \EmptyIterator() ;
		}
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
		if( !isset($this->arrExtensionInstances[$sName]) )
		{
			if( !$aExtMeta = $this->extensionMetainfo($sName) )
			{
				return null ;
			}
			$sClass = $aExtMeta->className() ;
			if(!class_exists($sClass))
			{
				throw new ExtensionException("找不到扩展 %s 指定的扩展类: %s",array($sName,$sClass)) ;
			}
			$aExtension = new $sClass($aExtMeta) ;
			$this->add($aExtension) ; 
		}
		return $this->arrExtensionInstances[$sName] ;
	}
	
	public function add(Extension $aExt)
	{
		$this->arrExtensionInstances[$aExt->metainfo()->name()] = $aExt ;
	}
	
	public function registerPackageNamespace($sNamespace,$sExtName)
	{
		$this->arrExtensionPackages[$sNamespace] = $sExtName ;
	}
	
	public function extensionNameByClass($sClass)
	{
		$nClassLen = strlen($sClass) ;
			
		for(end($this->arrExtensionPackages);$sNamespace=key($this->arrExtensionPackages);prev($this->arrExtensionPackages))
		{
			$nNamespaceLen = strlen($sNamespace) ;
			if( $nClassLen>$nNamespaceLen and substr($sClass,0,$nNamespaceLen)==$sNamespace and substr($sClass,$nNamespaceLen,1)=='\\' )
			{
				return current($this->arrExtensionPackages) ;
			}
		}
	}
		
	public function addInstalledExtension(ExtensionMetainfo $aExtMetainfo)
	{
		if( !isset($this->arrInstalledExtensions[$aExtMetainfo->name()]) )
		{
			$this->arrInstalledExtensions[$aExtMetainfo->name()] = $aExtMetainfo ;
		}
	}
	
	public function serialize()
	{
		$arrData = array(
				'arrEnableExtensiongNames' => &$this->arrEnableExtensiongNames ,
				'arrInstalledExtensions' => &$this->arrInstalledExtensions ,
				'arrExtensionPackages' => &$this->arrExtensionPackages ,
		) ;
		
		return serialize($arrData) ;
	}
	
	public function unserialize($serialized)
	{
		$this->__construct() ;
	
		$arrData = unserialize($serialized) ;
		$this->arrEnableExtensiongNames =& $arrData['arrEnableExtensiongNames'] ;
		$this->arrInstalledExtensions =& $arrData['arrInstalledExtensions'] ;
		$this->arrExtensionPackages =& $arrData['arrExtensionPackages'] ;
	}
	
	private $arrEnableExtensiongNames = array() ;
	
	private $arrInstalledExtensions = array() ;
		
	private $arrExtensionInstances = array() ;
	
	private $arrExtensionPackages = array() ;
	
	private $aSetting ;
}

?>