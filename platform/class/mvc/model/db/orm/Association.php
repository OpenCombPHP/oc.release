<?php
namespace oc\mvc\model\db\orm;

use org\jecat\framework\mvc\model\db\orm\Association as JcAssociation ;

class Association extends JcAssociation
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
