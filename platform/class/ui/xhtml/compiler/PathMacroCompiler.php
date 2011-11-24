<?php
namespace org\opencomb\ui\xhtml\compiler ;

use org\jecat\framework\ui\TargetCodeOutputStream;
use org\jecat\framework\ui\IObject;
use org\jecat\framework\ui\CompilerManager;
use org\jecat\framework\ui\xhtml\compiler\macro\PathMacroCompiler as JcPathMacroCompiler;

class PathMacroCompiler extends JcPathMacroCompiler
{
	
	public function compile(IObject $aObject,TargetCodeOutputStream $aDev,CompilerManager $aCompilerManager)
	{
		$sExtension = null ;

		$sContents = trim($aObject->source()) ;
		
		if( $sContents=='ext')
		{
			$sExtension = $aObject->root()->ns() ;
		}
		else if( substr($sContents,0,4)=='ext.' )
		{
			$sExtension = substr($sContents,4) ;
			if( $sExtension=='*' )
			{
				$sExtension = $aObject->root()->ns() ;
			}
			$sExtension = addslashes($sExtension) ;
		}
		
		if($sExtension)
		{
			$aDev->write( "if(\$aBelongsExt=\\org\\jecat\\framework\\system\\Application::singleton()->extensions()->extension('$sExtension')){\r\n" ) ;
			$aDev->write( "	\$aDevice->write(\$aBelongsExt->url()) ;" ) ;
			$aDev->write( "}" ) ;
		}
		else
		{
			parent::compile($aObject,$aDev,$aCompilerManager) ;
		}
	}
}

?>