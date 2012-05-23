<?php
namespace org\opencomb\platform\ui\xhtml\compiler ;

use org\opencomb\platform\service\Service;
use org\jecat\framework\ui\ObjectContainer;
use org\jecat\framework\ui\TargetCodeOutputStream;
use org\jecat\framework\ui\IObject;
use org\jecat\framework\ui\CompilerManager;
use org\jecat\framework\ui\xhtml\compiler\macro\PathMacroCompiler as JcPathMacroCompiler;

/**
 * @wiki /模板引擎/宏
 * @wiki 速查/模板引擎/宏
 * =={/ }平台path==
 *
 *  返回扩展文件的完整路径url
 * {|
 *  !属性
 *  !
 *  !类型
 *  !默认值
 *  !说明
 *  |---
 *  |
 *  |
 *  |
 *  |
 *  |
 *  |}
 *  [example php frameworktest template/test-template/macro/PlatFormPathMacroCase.html 2 6]
 *  
 *  [^]platform的{/}有别与framework的{/}，它处理的是extensions的url[/^]
 */

class PathMacroCompiler extends JcPathMacroCompiler
{
	
	public function compile(IObject $aObject,ObjectContainer $aObjectContainer,TargetCodeOutputStream $aDev,CompilerManager $aCompilerManager)
	{
		$sContents = trim($aObject->source()) ;
		
		if(substr($sContents,0,2)=='*.')
		{
			parent::compile($aObject,$aObjectContainer,$aDev,$aCompilerManager) ;
		}
		else
		{
			@list($sNamespace,$sPath) = explode(':',$sContents,2) ;
			if(!$sNamespace)
			{
				$sNamespace = $aObject->root()->ns() ;
			}
			
			if(!$sPath)
			{
				$aDev->output( "无效的{/}宏：".$sContents ) ;
			}
			else
			{
				$sUrl = addslashes(Service::singleton()->publicFolders()->find($sPath,$sNamespace,true)) ;
				$aDev->write( "\$aDevice->write(\"{$sUrl}\") ;" ) ;
			}
		}
	}
}

