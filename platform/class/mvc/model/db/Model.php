<?php
namespace oc\mvc\model\db ;

use oc\ext\Extension;

use jc\mvc\model\db\Model as JcModel ;

class Model extends JcModel
{
	
	/**
	 * 覆盖load方法是为了转换关联的属性性，这个作用应该通过 macro 在编译阶段完成
	 */
	public function load($values=null,$keys=null)
	{		
		return parent::load($values,$keys) ;
	}
	
	/**
	 * @return IModel
	 */
	public function child($sName)
	{
		return parent::child($sName) ;
	}
}

?>