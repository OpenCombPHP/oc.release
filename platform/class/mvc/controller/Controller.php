<?php
namespace oc\mvc\controller ;


use jc\mvc\controller\Controller as JcController ;

class Controller extends JcController
{
	/**
	 * @return oc\mvc\model\db\Model
	 */
    public function createModel($sName,$prototype,array $arrProperties=array(),$bAgg=false,$sClass='jc\\mvc\\model\\db\\Model')
    {
    	return parent::createModel($sName,$prototype,$arrProperties,$bAgg,'oc\\mvc\\model\\db\\Model') ;
    }
    
    /** 
     * @return oc\mvc\view\View
     */
    public function createView($sName,$sSourceFile)
    {
    	return parent::createView($sName,$sSourceFile,'oc\\mvc\\view\\View') ;
    }
}

?>