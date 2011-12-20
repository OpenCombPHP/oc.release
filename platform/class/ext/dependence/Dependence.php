<?php
namespace org\opencomb\platform\ext\dependence ;

use org\jecat\framework\util\VersionScope;

use org\opencomb\platform\ext\ExtensionMetainfo;

class Dependence
{
	static public function loadFromXml(\SimpleXMLElement $aDomInfo)
	{
		$aDependence = new self() ;
		
		foreach($aDomInfo->xpath('require') as $aRequireNode)
		{
			$aRequire = new RequireItem(
					empty($aRequireNode['type'])? RequireItem::TYPE_EXTENSION: (string)$aRequireNode['type']
					, empty($aRequireNode['item'])? null: (string)$aRequireNode['item']
					, VersionScope::fromString((string)$aRequireNode)
			) ;
			
			$aDependence->addRequire($aRequire) ;
		}
	}
	
	public function addRequire(RequireItem $aRequire)
	{
		$this->arrRequires[] = $aRequire ;
	}
	
	private $arrRequires = array() ;
	
}

?>