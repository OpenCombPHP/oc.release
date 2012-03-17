<?php
namespace org\opencomb\platform\lang\compile ;

use org\opencomb\platform\Platform;
use org\jecat\framework\lang\compile\CompilerFactory;

class OcCompilerFactory extends CompilerFactory
{
	/**
	 * return Compiler
	 */
	public function create()
	{
		$aCompiler = parent::create( new OcCompiler() ) ;
		
		$aCompiler->registerGenerator("org\\jecat\\framework\\lang\\compile\\object\\ClassDefine","org\\opencomb\\platform\\lang\\compile\\ClassDefineWrapper") ;
		
		return $aCompiler ;
	}
}

?>