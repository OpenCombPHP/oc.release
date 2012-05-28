<?php
namespace org\opencomb\platform\mvc\model ;

use org\jecat\framework\db\DB;

class Prototype
{
	static public function transTable(&$sTable,&$sPrototypeName)
	{
		// 原型名称
		if($sPrototypeName===null)
		{
			$pos = strpos($sTable,':') ;
			if( $pos!==false and $pos+1<strlen($sTable) )
			{
				$sPrototypeName = substr($sTable,$pos+1) ;
			}			
		}
		
		// 表前缀
		if( strstr($sTable,'`')===false )
		{
			$sTable = DB::singleton()->tableNamePrefix() . $sTable ;
		}
		
		$sTable = str_replace(':','_',$sTable) ;
	}
	
	
	/////////////////////
	
	
	static public function createBean(array & $arrConfig,$sNamespace='*',$bBuildAtOnce,\org\jecat\framework\bean\BeanFactory $aBeanFactory=null)
	{
		if( !empty($arrConfig['table']) and empty($arrConfig['disableTableTrans']) and strpos($arrConfig['table'],':')===false )
		{
			$arrConfig['table'] = $sNamespace.':'.$arrConfig['table'] ;
		}
			
		return parent::createBean($arrConfig,$sNamespace,$bBuildAtOnce,$aBeanFactory) ;
	}
	public function buildBean(array & $arrConfig,$sNamespace='*',\org\jecat\framework\bean\BeanFactory $aBeanFactory=null)
	{
		if( !empty($arrConfig['table']) and empty($arrConfig['disableTableTrans']) and strpos($arrConfig['table'],':')===false )
		{
			$arrConfig['table'] = $sNamespace.':'.$arrConfig['table'] ;
		}
		
		parent::buildBean($arrConfig,$sNamespace) ;
	}
	
	/**
	 * @brief 检查表 \a $sFullTableName 是否是扩展 $sExtName 的数据表
	 * @return boolean
	 * 检查规则: 若 $sFullTableName 以 ${sExtName}_开头，则返回true，否则返回false
	 */
	static public function isExtensionTable($sFullTableName,$sExtName)
	{
		$sTableNamePrefix = DB::singleton()->tableNamePrefix().$sExtName.'_' ;
		return strpos($sFullTableName,$sTableNamePrefix)===0 ;
	}
}

