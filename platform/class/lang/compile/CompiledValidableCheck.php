<?php
namespace org\opencomb\platform\lang\compile ;

use org\jecat\framework\lang\compile\object\NamespaceDeclare;

use org\jecat\framework\lang\oop\ClassLoader;

use org\jecat\framework\lang\aop\Aspect;
use org\jecat\framework\lang\compile\object\FunctionDefine;
use org\jecat\framework\lang\compile\object\TokenPool;
use org\jecat\framework\lang\compile\object\Token;
use org\jecat\framework\lang\compile\IGenerator;
use org\jecat\framework\lang\Object;

/**
 * 生成 用于检查编译文件有效性的代码
 */
class CompiledValidableCheck extends Object implements IGenerator
{
	public function generateTargetCode(TokenPool $aTokenPool, Token $aToken)
	{
		// 只在文件(TokenPool)的开头生成检查代码
		if( !$this->isFirstNamespaceToken($aTokenPool,$aToken) )
		{
			return ;
		}
		
		// 参考文件
		$arrReferFiles = $aTokenPool->sourcePath()? array($aTokenPool->sourcePath()): array() ;
		foreach($aTokenPool->properties()->get('arrAopWeavedStats')?: array() as $aAopWeaveStat)
		{
			if( $aAopWeaveStat->arrAdvices )
			{
				foreach($aAopWeaveStat->arrAdvices as $aAdvice)
				{
					if( $aAspect=$aAdvice->aspect() and $sFilePath=$aAspect->aspectFilepath() )
					{
						$arrReferFiles[] = $sFilePath ;
					}
				}
			}
		}
		
		// 生成代码
		$arrReferFiles = array_unique($arrReferFiles) ;
		$aValidCheckToken = new Token(0,$this->generateValidableCheckCode($arrReferFiles)) ;
		
		// 插入到 TokenPool 中
		$aTokenPool->insertAfter($aToken,$aValidCheckToken) ;
	}
	
	private function isFirstNamespaceToken(TokenPool $aTokenPool, Token $aToken)
	{
		foreach ($aTokenPool->iterator() as $aTmpToken)
		{
			if($aTmpToken instanceof NamespaceDeclare)
			{
				return $aTmpToken===$aToken ;
			}
		}
		return false ;
	}
	
	
	public function generateValidableCheckCode( $sClassName, array $arrReferFiles=array() )
	{
		$sCode = "\r\n\r\n// 检查编译文件的有效性\r\n" ;
		
		// 重新编译并加载类
		$sCodeRecompileClass = "	unlink(__FILE__) ; // 清理编译文件\r\n" ;
		$sCodeRecompileClass.= "	// TODO ... \r\n" ;
		$sCodeRecompileClass.= "	// 重新编译 Class \r\n" ;
		$sCodeRecompileClass.= "	\\org\\jecat\\framework\\lang\\oop\\ClassLoader::singleton()->load('{$sClassName}') ; // \r\n" ;
		$sCodeRecompileClass.= "	return ;\r\n" ;
		
		// 检查参考文件的时间
		$nCompileTime = time() ;
		foreach ($arrReferFiles as $sFilePath)
		{
			$sCode.= "if( filemtime('{$sFilePath}')>{$nCompileTime} ){\r\n" ;
			$sCode.= $sCodeRecompileClass ;
			$sCode.= "}\r\n" ;
		}
		
		// 检查 ClassLoader 的签名
		$sCompileClassLoaderSign = ClassLoader::singleton()->signature() ;
		$sCode.= "if( '{$sCompileClassLoaderSign}'!=\\org\\jecat\\framework\\lang\\oop\\ClassLoader::singleton()->signature() ){\r\n" ;
		$sCode.= $sCodeRecompileClass ;
		$sCode.= "}\r\n" ;
		
		return $sCode ;
	}
	
	
	// arrValidableReferFiles 暂时没有被使用
	static public function addReferFile(TokenPool $aTokenPool,$sFilepath)
	{
		$arrValidableReferFiles = $aTokenPool->properties()->get('arrValidableReferFiles')?: array() ;
		if( !in_array($sFilepath,$arrValidableReferFiles) )
		{
			$arrValidableReferFiles[] = $sFilepath ;
		}
		if($arrValidableReferFiles)
		{
			$aTokenPool->properties()->set('arrValidableReferFiles',$arrValidableReferFiles) ;
		}
	}
}

?>