<?php
namespace org\opencomb\platform\lang\compile ;

use org\jecat\framework\lang\Exception;

use org\jecat\framework\lang\compile\object\Token;

use org\jecat\framework\lang\compile\object\NamespaceDeclare;
use org\jecat\framework\lang\compile\object\TokenPool;
use org\opencomb\platform\Platform;
use org\opencomb\platform\system\PlatformFactory;
use org\jecat\framework\lang\oop\Package;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\lang\compile\Compiler;
use org\jecat\framework\lang\aop\AOP ;
use org\jecat\framework\lang\aop\compiler\ClassInfoLibrary ;

class OcCompiler extends Compiler
{
	public function compileClass($sClassName)
	{
		$this->sCompilingClassName = $sClassName ; 
		
		// 找到 class source 的路径
		if( !$sSourceFile = ClassLoader::singleton()->searchClass($sClassName,Package::nocompiled) )
		{
			throw new Exception("编译类时找不到类：%s",$sClassName) ; 
		}
		
		// 通过 class compiled package 确定 class compiled 的路径
		$aCompiledPackage = Package::flyweight(Package::compiled) ;
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
				
				if($this->isInitiativeCompileClass($sClassName))
				{
					// 生成 compiled 文件的 有效性检查代码
					$sCode.= $this->generateValidableCheckCode($sClassName,array($sSourceFile)) ;
				}
				else
				{
					$sCode.= "// {$sClassName} 被编译器本身依赖，编译文件失效后，无法主动重新编译\r\n\r\n" ;
				}
				
				$sCode.= "require \"".addslashes($sSourceFile)."\" ;\r\n" ;
				
				// 写入编译文件
				$sDirName = dirname($sCompiledFile) ;
				if( ! file_exists($sDirName) ){
					mkdir($sDirName,0755,true);
				}
				file_put_contents($sCompiledFile,$sCode) ;
			}
			
			// 系统 release 状态
			else
			{
				$sDirName = dirname($sCompiledFile) ;
				if( ! file_exists($sDirName) ){
					mkdir($sDirName,0755,true);
				}
				@copy($sSourceFile,$sCompiledFile) ;
			}
		}
	}
	
	
	public function generate(TokenPool $aTokenPool)
	{
		parent::generate($aTokenPool) ;
		
		// 仅在debug状态下
		if( !Platform::singleton()->isDebugging() )
		{
			return ;
		}
		
		// 在文件(TokenPool)的开头生成检查代码
		// -----------------------------------------------
		// 找到文件开头
		if( !$aNamespaceToken=$this->findFirstNamespaceToken($aTokenPool) )
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
		if($this->isInitiativeCompileClass($this->sCompilingClassName))
		{
			$arrReferFiles = array_unique($arrReferFiles) ;
			$aValidCheckToken = new Token(0,$this->generateValidableCheckCode($this->sCompilingClassName,$arrReferFiles)) ;
		}
		else
		{
			$aValidCheckToken = new Token(0,"// {$this->sCompilingClassName} 被编译器本身依赖，编译文件失效后，无法主动重新编译\r\n\r\n") ;
		}
		
		
		// 寻找 namespace 后的 ;
		$aIter = $aTokenPool->iterator() ;
		$aIter->search($aNamespaceToken->endToken());
		while($aIter->current()->tokenType() !== Token::T_SEMICOLON ){
			$aIter->next() ;
		}
		
		// 插入到 TokenPool 中
		$aTokenPool->insertAfter($aIter->current(),$aValidCheckToken) ;
	}
	
	private function isAopTarget($sSourceFile,$sClassName)
	{
		$aAOP = AOP::singleton();
		$aClassInfoLibrary = ClassInfoLibrary::singleton() ;
		
		$aTokenPool = null ;
		foreach($aAOP->jointPointIterator() as $aJointPoint){
			$sWeaveClass = $aJointPoint->weaveClass() ;
			
			// check by ClassInfoLibrary
			if( $aClassInfoLibrary->isA( $sClassName , $sWeaveClass ) ){
				
				// check by AOP
				if( null === $aTokenPool ){
					$aTokenPool = $this->scan($sSourceFile);
					$this->interpret($aTokenPool);
				}
				foreach($aTokenPool->iterator() as $aToken){
					if($aJointPoint->matchExecutionPoint($aToken) ){
						return true;
					}
				}
			}
		}
		
		return false;
	}
	
	
	private function findFirstNamespaceToken(TokenPool $aTokenPool)
	{
		foreach ($aTokenPool->iterator() as $aToken)
		{
			if($aToken instanceof NamespaceDeclare)
			{
				return $aToken ;
			}
		}
		return null ;
	}
	
	
	public function generateValidableCheckCode( $sClassName, array $arrReferFiles=array() )
	{
		$sCode = "\r\n\r\n// 检查编译文件的有效性\r\n" ;
	
		// 重新编译并加载类
		$sCodeRecompileClass = "	unlink(__FILE__) ; // 清理编译文件\r\n" ;
		$sCodeRecompileClass.= "	// 重新编译 Class \r\n" ;
		$sCodeRecompileClass.= "	\\org\\opencomb\\platform\\lang\\compile\\OcCompilerFactory::create()->compileClass('{$sClassName}') ; \r\n" ;
		$sCodeRecompileClass.= "	// 载入新编译的 Class \r\n" ;
		$sCodeRecompileClass.= "	\\org\\jecat\\framework\\lang\\oop\\ClassLoader::singleton()->load('{$sClassName}') ; // \r\n" ;
		$sCodeRecompileClass.= "	return ;\r\n" ;
	
		// 检查参考文件的时间
		foreach ($arrReferFiles as $sFilePath)
		{
			$nSourceFileTime = filemtime($sFilePath) ;
			$sCode.= "if( filemtime('{$sFilePath}')>{$nSourceFileTime} ){\r\n" ;
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
	
	public function isInitiativeCompileClass($sClass)
	{
		foreach(array(
				'org\\opencomb\\platform\\lang\\compile\\' ,
				'org\\jecat\\framework\\lang\\' ,
				//'org\\jecat\\framework\\lang\\compile\\' ,
				//'org\\jecat\\framework\\lang\\aop\\compiler\\' ,
				'org\\jecat\\framework\\io\\' ,
				'org\\jecat\\framework\\util\\' ,
				'org\\jecat\\framework\\pattern\\iterate\\' ,
		) as $sPackage)
		{
			if( strstr($sClass,$sPackage) !== false )
			{
				return false ;
			}
		}
		
		return true ;
	}

	
	private $sCompilingClassName ;
}

