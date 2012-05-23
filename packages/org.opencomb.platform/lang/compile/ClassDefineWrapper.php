<?php
namespace org\opencomb\platform\lang\compile ;

use org\jecat\framework\lang\compile\object\ClassDefine;
use org\jecat\framework\lang\Assert;
use org\jecat\framework\lang\compile\object\TokenPool;
use org\jecat\framework\lang\compile\object\Token;
use org\jecat\framework\lang\compile\IGenerator;
use org\jecat\framework\lang\Object;

/**
 * 在 aop 编译类时，为 类的定义 增加一层条件判断 if(true){ ... } ，
 * 在条件流程中定义的类，不会在文件执行前被声明，
 * 从而干扰 类定义文件的有效性判断
 * 
 * 根据反复测试，php 在执行一个文件前，会检查文件中定义的类，如果类的 extends 或 implements 中没有未加载的类，并且也不在条件流程中，
 * 该类会在执行文件以前就被php定义 。而蜂巢检查这个类定义文件是否有效的代码，在文件开头，这时类已经被定义完毕了。
 * 
 */
class ClassDefineWrapper extends Object implements IGenerator
{
	public function generateTargetCode(TokenPool $aTokenPool, Token $aObject)
	{
		Assert::must( $aObject instanceof ClassDefine ) ;
		
		$aTokenPool->insertBefore($aObject, new Token(T_STRING,"
if(true) // 在class定义外 套一层条件控制，避免 PHP 在执行当前文件前就完成类的定义，造成编译文件头部的有效性检查失效
{// ------------------
")) ;
		$aTokenPool->insertAfter($aObject->bodyToken()->theOther(), new Token(T_STRING,"
// ------------------
} // 在class定义外 套一层条件控制，避免 PHP 在执行当前文件前就完成类的定义，造成编译文件头部的有效性检查失效 ")) ;
	}
}

