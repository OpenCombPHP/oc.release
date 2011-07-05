<?php
namespace oc\mvc\model\db ;

use jc\mvc\model\db\Model as JcModel ;

class Model extends JcModel
{
	/**
	 * @return IModel
	 */
	public function child($sName)
	{
		if( !$this->isAggregation() )
		{
			orm\MAMap::transFullOrmName($sName) ;
		}
		
		return parent::child($sName) ;
	}
}

?>