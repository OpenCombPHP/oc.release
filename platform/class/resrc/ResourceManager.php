<?php
namespace org\opencomb\platform\resrc ;

use org\jecat\framework\fs\Folder;
use org\opencomb\platform\ext\Extension;
use org\jecat\framework\resrc\ResourceManager as JsResourceManager;

class ResourceManager extends JsResourceManager
{
	public function addFolder(Folder $aFolder,$sExtensionName=null)
	{
		if(!$sExtensionName)
		{
			$sExtensionName = Extension::retraceExtensionName() ;
		}
		
		parent::addFolder($aFolder,$sExtensionName) ;
	}
	
	public function removeFolder(Folder $aFolder,$sExtensionName=null)
	{
		if(!$sExtensionName)
		{
			$sExtensionName = Extension::retraceExtensionName() ;
		}
		
		parent::removeFolder($aFolder,$sExtensionName) ;
	}

	public function clearFolders($sExtensionName=null)
	{
		if(!$sExtensionName)
		{
			$sExtensionName = Extension::retraceExtensionName() ;
		}
		
		$this->arrFolders[$sExtensionName] = array() ;
	}
	
	/**
	 * @return org\jecat\framework\fs\File
	 */
	public function find($sFilename,$sNamespace='*')
	{
		if( $sNamespace=='*' and strstr($sFilename,':')===false )
		{
			$sNamespace = Extension::retraceExtensionName() ;
		}
		
		return parent::find($sFilename,$sNamespace) ;
	}
}
