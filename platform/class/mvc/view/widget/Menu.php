<?php
namespace org\opencomb\platform\mvc\view\widget ;

use org\jecat\framework\mvc\MVCEventManager;

use org\jecat\framework\mvc\view\widget\menu\Menu as JcMenu;

class Menu extends JcMenu
{
	
	static public function registerBuildHandle($sControllerClass,$sViewXPath,$sWidgetId,$fnHandle)
	{
		MVCEventManager::singleton()->registerEventHandle('buildBean',$fnHandle,$sControllerClass,$sViewXPath,$sWidgetId) ;
	}
	
	public function buildBean(array & $arrConfig,$sNamespace='*',\org\jecat\framework\bean\BeanFactory $aBeanFactory=null)
	{
		// 触发事件
		list($sControllerClass,$sViewXPath,$sWidgetId) = $this->mvcLocationInfo() ;
		$arrArgvs = array(&$arrConfig,&$sNamespace,$aBeanFactory) ;
		MVCEventManager::singleton()->emitEvent(__FUNCTION__,$arrArgvs,$sControllerClass,$sViewXPath,$sWidgetId) ;
		
		
		return parent::buildBean($arrConfig,$sNamespace,$aBeanFactory) ;
	}
    
	private function mvcLocationInfo()
	{
    	$sXPath = '' ;
    	if( !$aView = $this->view() )
    	{
    		return array(null,null,$this->id()) ;
    	}
    	
    	do {
    		if($sXPath)
    		{
    			$sXPath = '/' . $sXPath ;
    		}
    		$sXPath = $aView->name() . $sXPath ;
    		
    		if( $aController=$aView->controller() )
    		{
    			break ;
    		}
    		
    		if(!$aView->parent())
    		{
    			break ;
    		}
    		$aView = $aView->parent() ;
    	}while( 1 ) ;
    	
    	
    	return array( $aController?get_class($aController):null , $sXPath, $this->id() ) ;
		
	}
}


