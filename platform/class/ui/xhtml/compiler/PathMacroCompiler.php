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
 *
 * {|
 *  !{/ }
 *  !
 *  !{/ }返回一个文件的url
 *  |---
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
 *  
 *  [^]platform的{/}有别与framework的{/}，它处理的是extensions的url[/^]
 */
/**
 * @author anubis
 * @example /模板引擎/宏/自定义标签:name[1]
 *
 *  通过{/ }标签编译器的代码演示如何编写一个标签编译器
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
				list($aFolder,$sFileName) = Service::singleton()->publicFolders()->findEx($sPath,$sNamespace) ;
				$sUrl = $aFolder? addslashes($aFolder->httpUrl().'/'.$sFileName): '' ;
				$aDev->write( "\$aDevice->write(\"{$sUrl}\") ;" ) ;
			}
		}
	}
}

?>