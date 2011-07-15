<?php
namespace oc\mvc\controller ;

use jc\auth\AuthenticationException;
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
    

    /**
     * 
     * @see IController::mainRun()
     */
    public function mainRun ()
    {
	    try{
			
	    	$this->processChildren() ;
			
			$this->process() ;
			
    	}
    	catch (AuthenticationException $e)
    	{
    		foreach($this->viewContainer()->iterator() as $aView)
    		{
    			$aView->disable() ;
    		}
    		
    		$aController = new PermissionDenied($this->aParams) ;
    		$this->add($aController) ;
    		
    		$aController->process() ;
    		
    	}
    	
    	$this->displayViews() ;
    }
    
	public function permissionDenied($sMessage=null,array $arrArgvs=array())
	{
		throw new AuthenticationException($this,$sMessage,$arrArgvs) ;
	}
	
}

?>