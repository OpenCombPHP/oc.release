<?php
namespace org\opencomb\platform\lang\compile ;

use org\jecat\framework\lang\compile\CompilerFactory;

class OcCompilerFactory extends CompilerFactory
{
	/**
	 * return Compiler
	 */
	public function create()
	{
		$aCompiler = parent::create( new OcCompiler() ) ;

		// 编译文件有效性检查的代码生成器
		$aCompiler->registerGenerator("org\\jecat\\framework\\lang\\compile\\object\\NamespaceDeclare","org\\opencomb\\platform\\lang\\compile\\CompiledValidableCheck") ;
		
		return $aCompiler ;
	}
}

?>