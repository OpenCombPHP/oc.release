<?php
namespace oc\system ;

use jc\system\HttpAppFactory;
use jc\system\CoreApplication;

class PlatformFactory extends HttpAppFactory
{
	public function createClassLoader(CoreApplication $aApp)
	{
		$aClassLoader = parent::createClassLoader($aApp) ;
		
		// class
		$aClassLoader->addPackage(dirname(__DIR__),'oc') ;

		return $aClassLoader ;
	}
	public function createAccessRouter(CoreApplication $aApp)
	{
		return new AccessRouter() ;
	}
}

?>