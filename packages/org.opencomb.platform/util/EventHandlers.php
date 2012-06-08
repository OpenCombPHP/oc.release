<?php
namespace org\opencomb\platform\util ;

use org\jecat\framework\util\EventReturnValue;

use org\jecat\framework\db\DB;

use org\jecat\framework\mvc\view\UIFactory;

use org\jecat\framework\mvc\view\View;
use org\opencomb\platform\ext\ExtensionManager;
use org\jecat\framework\mvc\model\Prototype;
use org\jecat\framework\util\EventManager;
use org\jecat\framework\mvc\controller\Controller;

class EventHandlers
{
	static public function registerEventHandlers(EventManager $aEventManager)
	{
		$aEventManager->registerEventHandle(
				'org\\jecat\\framework\\mvc\\controller\\Controller'
				, Controller::createDefaultView
				, array(__CLASS__,'createDefaultView')
		) ;
		
		$aEventManager->registerEventHandle(
				'org\\jecat\\framework\\mvc\\model\\Prototype'
				, Prototype::transTable
				, array(__CLASS__,'transTable')
		) ;
	}
	
	/**
	 * 默认的模板名称
	 */
	static public function createDefaultView(Controller $aController)
	{
		// 用自己的类名做为模板文件名创建一个视图
		$sClassName = get_class($aController) ;
		$sExtensionName = ExtensionManager::singleton()->extensionNameByClass( $sClassName ) ;
		
		// 无法确定所属目录
		if(!$sExtensionName)
		{
			$sTemplate = null ;
			return ;
		}

		// 子目录
		$arrSlices = explode('\\', $sClassName) ;
		if( count($arrSlices)>3 )		// 去掉前面的3段（org/com,组织名,扩展名）
		{
			$arrSlices = array_slice($arrSlices,3) ;
			$sFileName = implode('/',$arrSlices).'.html' ;
		}
		else
		{
			$sFileName = str_replace('\\','.',$sClassName).'.html' ;
		}
		
		if( UIFactory::singleton()->sourceFileManager()->find($sFileName,$sExtensionName) )
		{
			return new EventReturnValue(new View($sExtensionName.':'.$sFileName)) ;
		}		
	}
	

	/**
	 * 转换表名
	 */
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
}
