<?php
namespace org\opencomb\platform\ui\xhtml\compiler ;

use org\jecat\framework\ui\ObjectContainer;
use org\jecat\framework\ui\TargetCodeOutputStream;
use org\jecat\framework\ui\IObject;
use org\jecat\framework\ui\CompilerManager;
use org\jecat\framework\ui\xhtml\compiler\macro\PathMacroCompiler as JcPathMacroCompiler;

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
				$aDev->write( "if(\$aFile=\\org\\opencomb\\platform\\Platform::singleton()->publicFolders()->find(\"$sPath\",\"$sNamespace\")){\r\n" ) ;
				$aDev->write( "	\$aDevice->write(\$aFile->httpUrl()) ;" ) ;
				$aDev->write( "}" ) ;
			}
		}
	}
}

?>