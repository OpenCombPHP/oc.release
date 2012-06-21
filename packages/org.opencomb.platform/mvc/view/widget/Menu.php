<?php
namespace org\opencomb\platform\mvc\view\widget ;

use org\jecat\framework\lang\Exception;
use org\jecat\framework\util\EventManager;
use org\jecat\framework\mvc\view\widget\menu\Menu as JcMenu;

class Menu extends JcMenu
{
	
	static public function registerBuildHandle($sControllerClass,$sViewXPath,$sWidgetId,$fnHandle,array $arrCallbackArgvs=null)
	{
		$aRefFunc = is_array($fnHandle) ?
			new \ReflectionMethod($fnHandle[0],$fnHandle[1]) :
			new \ReflectionFunction($fnHandle) ;
		if( $aRefFunc instanceof \ReflectionMethod )
		{
			if(!$aRefFunc->isStatic())
			{
				throw new Exception("必须使用 static 方法做为 Menu::registerBuildHandle() 的事件回调函数。") ;
			}
			if(!$aRefFunc->isPublic())
			{
				throw new Exception("必须使用 public 方法做为 Menu::registerBuildHandle() 的事件回调函数。") ;
			}
		}

		$sObjectId = $sControllerClass.'-'.$sViewXPath.'-'.$sWidgetId ;
		EventManager::singleton()->registerEventHandle(
						__CLASS__
						, 'beforeBuildBean'
						, $fnHandle
						, $arrCallbackArgvs
						, $sObjectId
		) ;
	}
	
	public function buildBean(array & $arrConfig,$sNamespace='*',\org\jecat\framework\bean\BeanFactory $aBeanFactory=null)
	{
		// 触发事件
    	if( !$aView = $this->view() )
    	{
			$sObjectId = '--'.$this->id() ;
    	}
    	else	
    	{
    		$aController = $aView->controller(true) ;
			$sObjectId = ($aController?get_class($aController):null).'-'.$aView->xpath(false).'-'.$this->id() ;
    	}
		
		$arrArgvs = array(&$arrConfig,&$sNamespace,$aBeanFactory) ;		
		EventManager::singleton()->emitEvent(__CLASS__,'beforeBuildBean',$arrArgvs,$sObjectId) ;
		
		return parent::buildBean($arrConfig,$sNamespace,$aBeanFactory) ;
	}
    
	private function mvcLocationInfo()
	{
    	$sXPath = '' ;
    	if( !$aView = $this->view() )
    	{
    		return array(null,null,$this->id()) ;
    	}
    	else	
    	{
    		$aController = $aView->controller(true) ;
    		return array( $aController?get_class($aController):null , $aView->xpath(false), $this->id() ) ;
    	}
		
	}
}



