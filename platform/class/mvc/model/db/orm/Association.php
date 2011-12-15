<?php
namespace org\opencomb\platform\mvc\model\db\orm;

use org\jecat\framework\mvc\model\db\orm\Association as JcAssociation ;

class Association extends JcAssociation
{
	public function buildBean(array & $arrConfig,$sNamespace='*',\org\jecat\framework\bean\BeanFactory $aBeanFactory=null)
	{
		if(empty($arrConfig['disableBridgeTableTrans']))
		{
			if( !empty($arrConfig['bridge']) )
			{
				$arrConfig['bridge'] = Prototype::transTableName($arrConfig['bridge'],$sNamespace) ;
			}
		}
		
		parent::buildBean($arrConfig,$sNamespace) ;
	}
}
