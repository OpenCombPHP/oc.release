<?php
namespace oc\system ;

use oc\ext\Extension;
use jc\system\Request ;
use jc\system\AccessRouter as JcAccessRouter;

class AccessRouter extends JcAccessRouter
{
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
	    		$sExtensionName = 'oc' ;
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
    		return new $sControllerClass ;
    	}
    	else
    	{
    		return null ;
    	}
    }
}

?>