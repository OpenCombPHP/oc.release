<?php
namespace org\opencomb\platform\lang\compile ;

use org\opencomb\platform\Platform;
use org\opencomb\platform\system\PlatformFactory;
use org\jecat\framework\lang\oop\Package;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\lang\compile\Compiler;

class OcCompiler extends Compiler
{
	public function compileClass($sClassName)
	{
		// 找到 class source 的路径
		$sSourceFile = ClassLoader::singleton()->searchClass($sClassName,Package::nocompiled) ;
		
		// 通过 class compiled package 确定 class compiled 的路径
		$aCompiledPackage = PlatformFactory::singleton()->classCompiledPackage() ;
		list($sSubFolder,$sShortClassName) = $aCompiledPackage->parsePath($sClassName) ;
		$sCompiledFile = $aCompiledPackage->folder()->path() . '/' . $sSubFolder . '/' . $sShortClassName . '.php' ;
		
		// AOP 目标
		if( $this->isAopTarget($sSourceFile,$sClassName) )
		{
			$this->compile($sSourceFile,$sCompiledFile) ;
		}
		
		// 非 AOP目标类
		else
		{
			// 系统 debug 状态
			if( Platform::singleton()->isDebugging() )
			{
				$sCode = "<?php \r\n" ;
				
				// 生成 compiled 文件的 有效性检查代码
				$sCode.= CompiledValidableCheck::singleton()->generateValidableCheckCode($sClassName,array($sSourceFile)) ;
				
				$sCode.= "require \"".addslashes($sSourceFile)."\" ;\r\n" ;
				
				// 写入编译文件
				file_put_contents($sCompiledFile,$sCode) ;
			}
			
			// 系统 release 状态
			else
			{
				copy($sSourceFile,$sCompiledFile) ;
			}
		}
	}
	
	private function isAopTarget($sSourceFile,$sClassName)
	{
		// ClassInfoLibrary
		// todo ...
		
		// for all jointpoints
		// todo ...
	}
}
