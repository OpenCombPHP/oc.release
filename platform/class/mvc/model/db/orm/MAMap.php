<?php
namespace oc\mvc\model\db\orm ;

use jc\mvc\model\db\orm\AssociationPrototype;

use oc\ext\Extension;

use jc\mvc\model\db\orm\ModelAssociationMap;

class MAMap extends ModelAssociationMap
{
	public function addOrm(array $arrOrm,$bCheck=true)
	{
		self::transFullOrmNameForCfg(
				$arrOrm, Extension::retraceExtensionName()
		) ;
		
		if( empty($arrOrm['class']) )
		{
			$arrOrm['class'] = 'oc\\mvc\\model\\db\\Model' ;
		}
		
		return parent::addOrm($arrOrm,$bCheck) ;
	}
	
	public function fragment($sPrototypeName,array $arrAssocFragment=array(),$bRetPrototype=true)
	{
		if( $sExtensionName = Extension::retraceExtensionName() )
		{
			self::transFullOrmName($sPrototypeName,$sExtensionName) ;
			
			$fnTranslater = null ;
			$fnTranslater = function (array &$arrFragment=array(),$fnTranslater) use ($sExtensionName)
			{
				foreach($arrFragment as $name=>&$property)
				{
					if( is_string($property) )
					{
						MAMap::transFullOrmName($property,$sExtensionName) ;
					}
					
					else 
					{
						$fnTranslater($property,$fnTranslater) ;
						
						$sFullName = $name ;
						MAMap::transFullOrmName($sFullName,$sExtensionName) ;
						$arrFragment[$sFullName] =& $property ;
						
						unset($arrFragment[$name]) ;
					}
				}
			} ;
			
			$fnTranslater($arrAssocFragment,$fnTranslater) ;
			// call_user_func_array( $fnTranslater, array(&$arrAssocFragment,$sExtensionName,$fnTranslater) ) ;
		}
		
		return parent::fragment($sPrototypeName,$arrAssocFragment,$bRetPrototype) ;
	}
	
	static public function transFullOrmNameForCfg(array &$arrOrm,$sExtensionName)
	{
		if(!$sExtensionName)
		{
			return ;
		}
	
		if( empty($arrOrm['name']) )
		{
			$arrOrm['name'] = $arrOrm['table'] ;
		}
		self::transFullOrmName($arrOrm['name'],$sExtensionName) ;
		
		self::transFullOrmName($arrOrm['table'],$sExtensionName,true) ;
		
		foreach(AssociationPrototype::allAssociationTypes() as $sType)
		{
			if( !empty($arrOrm[$sType]) )
			{
				foreach($arrOrm[$sType] as &$arrAssoCfg)
				{
					if( !empty($arrAssoCfg['model']) )
					{
						self::transFullOrmName($arrAssoCfg['model'],$sExtensionName) ;
					}
					if( !empty($arrAssoCfg['prop']) )
					{
						self::transFullOrmName($arrAssoCfg['prop'],$sExtensionName) ;
					}
					if( !empty($arrAssoCfg['bridge']) )
					{
						self::transFullOrmName($arrAssoCfg['bridge'],$sExtensionName,true) ;
					}
				}
			}
		}
	}
	
	static public function transFullOrmName(&$sName,$sExtensionName=null,$bTableName=false)
	{
		if( strstr($sName,':')===false )
		{
			if(!$sExtensionName)
			{
				$sExtensionName = Extension::retraceExtensionName() ;
			}
			
			$sName = $sExtensionName.':'.$sName ;
		}
		
		if($bTableName)
		{
			$sName = str_replace(':','_',$sName) ;
		}
	}
	
}

?>