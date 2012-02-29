<?php
namespace org\opencomb\platform\mvc\model\db\orm ;

use org\jecat\framework\mvc\model\db\orm\Prototype as JcPrototype ;

class Prototype extends JcPrototype
{
	static public function createBean(array & $arrConfig,$sNamespace='*',$bBuildAtOnce,\org\jecat\framework\bean\BeanFactory $aBeanFactory=null)
	{
		if( !empty($arrConfig['table']) and empty($arrConfig['tableTransed']) and empty($arrConfig['disableTableTrans']) )
		{
			$arrConfig['table'] = self::transTableName($arrConfig['table'],$sNamespace) ;
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
				$arrConfig['table'] = self::transTableName($arrConfig['table'],$sNamespace) ;
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
	
	/**
	 * @brief 检查表 \a $sFullTableName 是否是扩展 $sExtName 的数据表
	 * @return boolean
	 * 检查规则: 若 $sFullTableName 以 ${sExtName}_开头，则返回true，否则返回false
	 */
	static public function isExtensionTable($sFullTableName,$sExtName){
		function startsWith($haystack, $needle){
			$length = strlen($needle);
			return (substr($haystack, 0, $length) === $needle);
		}
		$sPrefix = '';
		if( startsWith($sFullTableName,$sPrefix.$sExtName.'_')){
			return true;
		}
		return false ;
	}
}

?>
