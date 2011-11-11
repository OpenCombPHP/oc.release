<?php
namespace oc\mvc\controller ;

use jc\mvc\model\db\orm\Prototype;
use jc\auth\IdManager;
use oc\ext\Extension;
use jc\auth\AuthenticationException;
use jc\mvc\controller\Controller as JcController ;

class Controller extends JcController
{
    /**
     * properties:
     * 	name				string						名称
     * 	params				array,jc\util\IDataSrc 		参数
     *  model.ooxx			config
     *  view.ooxx			config
     *  controller.ooxx		config
     * 
     * @see jc\bean\IBean::build()
     */
    public function build(array & $arrConfig,$sNamespace='*')
    {
    	if($sNamespace=='*')
    	{
    		$sNamespace = $this->application()->extensions()->extensionNameByClass( get_class($this) )?: '*' ;
    	}
    	
    	return parent::build($arrConfig,$sNamespace) ;
    }
    
	/**
	 * @return oc\mvc\model\db\Model
	 */
    public function createModel($prototype,array $arrProperties=array(),$bAgg=false,$sName=null,$sClass='jc\\mvc\\model\\db\\Model')
    {
    	if( !$sName )
    	{
    		if( is_string($prototype) )
    		{
    			$sName = $prototype ;
    		}
    		else if( is_array($prototype) )
    		{
    			if( !empty($prototype['name']) )
    			{
    				$sName = $prototype['name'] ;
    			}
    			else if ( !empty($prototype['table']) )
    			{
    				$sName = $prototype['table'] ;
    			}
    		}
    		else if( $prototype instanceof Prototype )
    		{
    			$sName = $prototype->name() ;
    		}
    	}
    	
    	return parent::createModel($prototype,$arrProperties,$bAgg,$sName,'oc\\mvc\\model\\db\\Model') ;
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
    		$aController = new PermissionDenied($this->aParams) ;
    		$this->add($aController) ;
    		
    		$aController->mainRun() ;
    	}
    }
    
    protected function requireLogined($sMessage=null,array $arrArgvs=array()) 
    {
    	if( !IdManager::fromSession()->currentId() )
    	{
    		$this->permissionDenied($sMessage,$arrArgvs) ;
    	}
    }
    
	protected function permissionDenied($sMessage=null,array $arrArgvs=array())
	{
		throw new AuthenticationException($this,$sMessage,$arrArgvs) ;
	}

    public function createFrame()
    {
    	return new FrontFrame() ;
    }
}

?>