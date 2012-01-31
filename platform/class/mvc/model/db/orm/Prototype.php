<?php
namespace org\opencomb\platform\mvc\model\db\orm ;

use org\jecat\framework\mvc\model\db\orm\Prototype as JcPrototype ;

class Prototype extends JcPrototype
{
	static public function createBean(array & $arrConfig,$sNamespace='*',$bBuildAtOnce,\org\jecat\framework\bean\BeanFactory $aBeanFactory=null)
	{
		if( !empty($arrConfig['table']) )
		{
			self::transTableNameRef($arrConfig['table'],$sNamespace) ;
			$arrConfig['tableTransed'] = true ;
		}
			
		return parent::createBean($arrConfig,$sNamespace,$bBuildAtOnce,$aBeanFactory) ;
	}
	public function buildBean(array & $arrConfig,$sNamespace='*',\org\jecat\framework\bean\BeanFactory $aBeanFactory=null)
	{
		if(empty($arrConfig['disableTableTrans']))
		{
			if( empty($arrConfig['table']) and !empty($arrConfig['name']) )
			{
				$arrConfig['table'] = $arrConfig['name'] ;
			}
			
			if( !empty($arrConfig['table']) and empty($arrConfig['tableTransed']) )
			{
				self::transTableNameRef($arrConfig['table'],$sNamespace) ;
			}
		}
		
		parent::buildBean($arrConfig,$sNamespace) ;
	}
	
	static public function transTableName($sTableName,$sNamespace)
	{
		return self::transTableNameRef($sTableName,$sNamespace);
	}
	static public function transTableNameRef(&$sTableName,&$sNamespace)
	{
		if(strpos($sTableName,':')!==false)
		{
			list($sNamespace,$sTableName) = explode(':',$sTableName) ;
		}
		return $sTableName = $sNamespace . '_' . $sTableName ;
	}
}

?>