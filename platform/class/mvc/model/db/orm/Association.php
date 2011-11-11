<?php
namespace oc\mvc\model\db\orm;

use jc\mvc\model\db\orm\Prototype;

class Association
{
	public function build(array & $arrConfig,$sNamespace='*')
	{
		if(empty($arrConfig['disableBridgeTableTrans']))
		{
			if( !empty($arrConfig['bridge']) )
			{
				$arrConfig['bridge'] = Prototype::transTableName($arrConfig['bridge'],$sNamespace) ;
			}
		}
		
		parent::build($arrConfig,$sNamespace) ;
	}
}
?>
