<?php
namespace oc\ui ;

use jc\fs\Dir;
use oc\ext\Extension ;
use jc\ui\SourceFileManager as JcSourceFileManager ;

class SourceFileManager extends JcSourceFileManager
{
	public function addFolder($sPath,$sExtensionName=null)
	{
		if(!$sExtensionName)
		{
			$sExtensionName = Extension::retraceExtensionName() ;
		}
		
		parent::addFolder($sPath,$sExtensionName) ;
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