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
		return parent::create( new OcCompiler() ) ;
	}
}

?>