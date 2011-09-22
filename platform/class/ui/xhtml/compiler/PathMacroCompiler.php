<?php
namespace oc\ui\xhtml\compiler ;

use jc\ui\TargetCodeOutputStream;
use jc\ui\IObject;
use jc\ui\CompilerManager;
use jc\ui\xhtml\compiler\macro\PathMacroCompiler as JcPathMacroCompiler;

class PathMacroCompiler extends JcPathMacroCompiler
{
	
	public function compile(IObject $aObject,TargetCodeOutputStream $aDev,CompilerManager $aCompilerManager)
	{
		$sExtension = null ;

		$sContents = trim($aObject->source()) ;
		
		if( $sContents=='ext')
		{
			$sExtension = $aCompilerManager->compilingStatus()->sourceNamespace() ;
		}
		else if( substr($sContents,0,4)=='ext.' )
		{
			$sExtension = substr($sContents,4) ;
			if( $sExtension=='*' )
			{
				$sExtension = $aObject->parent()->ns() ;
			}
			$sExtension = addslashes($sExtension) ;
		}
		
		if($sExtension)
		{
			$aDev->write( "if(\$aBelongsExt=\\jc\\system\\Application::singleton()->extensions()->extension('$sExtension')){\r\n" ) ;
			$aDev->write( "	echo \$aBelongsExt->url() ;" ) ;
			$aDev->write( "}" ) ;
		}
		else
		{
			parent::compile($aObject,$aDev,$aCompilerManager) ;
		}
	}
}

?>