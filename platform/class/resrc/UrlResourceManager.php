<?php
namespace oc\resrc ;

use jc\fs\Dir ;
use oc\ext\Extension ;
use jc\resrc\UrlResourceManager as JsUrlResourceManager ;

class UrlResourceManager extends JsUrlResourceManager
{
	public function addFolder($sPath,$sUrlPrefix=null,$sExtensionName=null)
	{
		if(!$sExtensionName)
		{
			$sExtensionName = Extension::retraceExtensionName() ;
		}
		
		parent::addFolder($sPath,$sUrlPrefix,$sExtensionName) ;
	}
	
	public function removeFolder($sPath,$sExtensionName=null)
	{
		if(!$sExtensionName)
		{
			$sExtensionName = Extension::retraceExtensionName() ;
		}
		
		parent::removeFolder($sPath,$sExtensionName) ;
	}

	public function clearFolders($sExtensionName=null)
	{
		if(!$sExtensionName)
		{
			$sExtensionName = Extension::retraceExtensionName() ;
		}
		
		$this->arrFolders[$sExtensionName] = array() ;
	}
	
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