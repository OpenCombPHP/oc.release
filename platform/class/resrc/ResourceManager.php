<?php
namespace oc\resrc ;

use jc\fs\IFolder;
use oc\ext\Extension ;
use jc\resrc\ResourceManager as JsResourceManager ;

class ResourceManager extends JsResourceManager
{
	public function addFolder(IFolder $aFolder,$sExtensionName=null)
	{
		if(!$sExtensionName)
		{
			$sExtensionName = Extension::retraceExtensionName() ;
		}
		
		parent::addFolder($aFolder,$sExtensionName) ;
	}
	
	public function removeFolder(IFolder $aFolder,$sExtensionName=null)
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
	 * @return jc\fs\IFile
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

?>