<?php
namespace oc\mvc\controller ;

use oc\base\FrontFrame;

use oc\ext\Extension;

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
    public function createView($sName=null,$sSourceFile=null)
    {
	    if( !$sName )
	    {
	    	$sName = $this->name() ;
	    }
    
	    if( !$sSourceFile )
	    {
	    	$sSourceFile = Extension::retraceExtensionName() . ':' . $sName . '.html' ;
	    }
	    
	    if( strstr($sSourceFile,':')===false )
	    {
	    	$sSourceFile = Extension::retraceExtensionName() . ':' . $sSourceFile ;
	    }
    	
    	return parent::createView($sName,$sSourceFile,'oc\\mvc\\view\\View') ;
    }

    public function createFormView($sName=null,$sSourceFile=null)
    {
    	return $this->createView($sName,$sSourceFile,'oc\\mvc\\view\\FormView') ;
    }

    /**
     * 
     * @see IController::mainRun()
     */
    public function mainRun ()
    {
	    try{
	    	parent::mainRun() ;
    	}
    	catch (AuthenticationException $e)
    	{
    		foreach($this->viewContainer()->iterator() as $aView)
    		{
    			$aView->disable() ;
    		}
    		
    		$aController = new PermissionDenied($this->aParams) ;
    		$this->add($aController) ;
    		
    		$aController->mainRun() ;
    	}
    }
    
	public function permissionDenied($sMessage=null,array $arrArgvs=array())
	{
		throw new AuthenticationException($this,$sMessage,$arrArgvs) ;
	}

    public function createFrame()
    {
    	return new FrontFrame() ;
    }
}

?>