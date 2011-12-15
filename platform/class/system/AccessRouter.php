<?php
namespace org\opencomb\platform\system ;

use org\opencomb\platform\ext\Extension;
use org\jecat\framework\system\Request ;
use org\jecat\framework\system\AccessRouter as JcAccessRouter;

class AccessRouter extends JcAccessRouter
{
    /**
     * Enter description here ...
     * 
     * @return void
     */
    public function setDefaultController($sControllerName,$sExtensionName=null)
    {
    	if( $sExtensionName===null )
    	{
    		$sExtensionName = Extension::retraceExtensionName() ;
    		
	    	if( !$sExtensionName )
	    	{
	    		$sExtensionName = 'org.opencomb' ;
	    	}
    	}
    	
    	parent::setDefaultController($sControllerName,$sExtensionName) ;
    }
    
    public function addController($sControllerClass,$sControllerName=null,$sExtensionName=null)
    {
    	if(!$sControllerName)
    	{
    		$sControllerName = basename($sControllerClass) ;
    	}
    	
    	if( $sExtensionName===null )
    	{
    		$sExtensionName = Extension::retraceExtensionName() ;
    		
	    	if( !$sExtensionName )
	    	{
	    		$sExtensionName = 'org.opencomb' ;
	    	}
    	}
    	
    	if($sExtensionName)
    	{
    		$sControllerName = $sExtensionName.'.'.$sControllerName ;
    	}
    	
    	parent::addController($sControllerClass,$sControllerName) ;
    }
    
    /**
     * 在 oc 中固定使用 c 作为控制器的参数名，e 作为控制器所属的扩展名
     */
    public function createRequestController(Request $aRequest)
    {
    	// 
    	if( $sControllerName=$aRequest->get('c') )
    	{
    		if($sExtensionName=$aRequest->get('e'))
    		{
    			$sControllerName = $sExtensionName.'.'.$sControllerName ;
    		}
    	}
    	else 
    	{
    		$sControllerName = $this->defaultController() ;
    	}
    	
    	if(!$sControllerName)
    	{
    		return null ;
    	}
 
    	// 通过名称查找注册过的控制器
    	if( !$sControllerClass=$this->controller($sControllerName) )
    	{
	    	// 尝试将 controller name 转换为 class
	    	$sControllerClass = str_replace(".","\\",$sControllerName) ;
    	}
   
    	if( class_exists($sControllerClass) )
    	{
    		return new $sControllerClass($aRequest) ;
    	}
    	else
    	{
    		return null ;
    	}
    }
}

?>