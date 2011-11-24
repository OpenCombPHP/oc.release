<?php
namespace oc\mvc\model\db\orm ;

use org\jecat\framework\mvc\model\db\orm\Prototype as JcPrototype ;

class Prototype extends JcPrototype
{
	public function build(array & $arrConfig,$sNamespace='*')
	{
		if(empty($arrConfig['disableTableTrans']))
		{
			if( empty($arrConfig['table']) and !empty($arrConfig['name']) )
			{
				$arrConfig['table'] = $arrConfig['name'] ;
			}
			
			if( !empty($arrConfig['table']) )
			{
				$arrConfig['table'] = self::transTableName($arrConfig['table'],$sNamespace) ;
			}
		}
		
		parent::build($arrConfig,$sNamespace) ;
	}
	
	static public function transTableName($sTableName,$sNamespace)
	{
		return $sNamespace . '_' . $sTableName ;
	}
}

?>