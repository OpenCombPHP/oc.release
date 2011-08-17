<?php
namespace oc\ui ;

use jc\fs\IFolder;

use jc\fs\Dir;
use oc\ext\Extension ;
use jc\ui\SourceFileManager as JcSourceFileManager ;

class SourceFileManager extends JcSourceFileManager
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

	public function detectNamespace($sFilename)
	{
		if( ($nPos=strpos($sFilename,':'))!==false )
		{
			return array(substr($sFilename,0,$nPos),substr($sFilename,$nPos+1)) ;
		}
		else 
		{
			return array(Extension::retraceExtensionName(), $sFilename) ;
		}
	}
}

?>