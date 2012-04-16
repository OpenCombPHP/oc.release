<?php
namespace org\opencomb\platform\resrc ;

use org\jecat\framework\fs\Folder;
use org\opencomb\platform\ext\Extension;

class PublicResourceManager extends ResourceManager implements \Serializable
{
	public function __construct(Folder $aPublicFolder)
	{
		$this->aPublicFolder = $aPublicFolder ;
	}
	
	/**
	 * @return org\jecat\framework\fs\File
	 */
	public function find($sFilename,$sNamespace='*',$bHttpUrl=false)
	{
		if( strstr($sFilename,':')!==false )
		{
			list($sNamespace,$sFilename) = explode(':', $sFilename, 2) ;
		}
		
		$sSubPath = $sNamespace. '/' . $sFilename ;
		
		if( $aFSO=$this->aPublicFolder->find($sSubPath) )
		{
			return $bHttpUrl? $aFSO->httpUrl(): $aFSO ;
		}
		else
		{
			return null ;
		}
	}
	
	public function importFromSourceFolders()
	{
		foreach( $this->folderNamespacesIterator() as $sNamespace )
		{
			foreach( $this->folderIterator($sNamespace) as $aFolder )
			{
				foreach($aFolder->iterator() as $sSubPath)
				{
					// 过滤已知版本库
					if( preg_match('`(^|/)(\\.svn|\\.git|\\.cvs)(/|$)`',$sSubPath) )
					{
						continue ;
					}
					
					$sTarget = $this->aPublicFolder->path().'/'.$sNamespace.'/'.$sSubPath ;
					$sSource = $aFolder->path().'/'.$sSubPath ;
					
					if( is_dir($sSource) )
					{
						if( !file_exists($sTarget) )
						{
							mkdir($sTarget,(Folder::CREATE_DEFAULT&0777),true) ;						
						}
					}
					else
					{
						$sFolderPath = dirname($sTarget) ;
						if( !is_dir($sFolderPath) )
						{
							mkdir($sTarget,($sFolderPath::CREATE_DEFAULT&0777),true) ;
						}
						copy( $sSource, $sTarget ) ;						
					}
				}
			}
		}
	}
	
	/**
	 * @return org\jecat\framework\fs\Folder
	 */
	public function publicFolder()
	{
		return $this->aPublicFolder ;
	}

	public function serialize()
	{
		return serialize(array(
			'aPublicFolder' => $this->aPublicFolder ,
			'arrFolders' => &$this->arrFolders ,
		)) ;
	}
	
	public function unserialize($serialized)
	{
		$arrData = unserialize($serialized) ;
		$this->aPublicFolder = $arrData['aPublicFolder'] ;
		$this->arrFolders =& $arrData['arrFolders'] ;
	}
	
	private $aPublicFolder ;
}
