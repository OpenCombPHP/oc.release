<?php
namespace org\opencomb\platform\system ;

use org\opencomb\platform\service\Service;

use org\opencomb\platform\Platform;

use org\jecat\framework\session\OriginalSession;

class OcSession extends OriginalSession
{
	/**
	 * @return bool
	 */
	public function start()
	{
		if( $this->hasStarted() )
		{
			return true ;
		}
		
		$this->updateSignature() ;
		
		return parent::start() ;
	}
	
	public function updateSignature(){
		// 检查 cookie 中的 session 签名
		// 系统已经发生变化，清理 session
		$sSystemSessionVerion = $this->sessionVerion() ; 
		if( empty($_COOKIE['oc_session_signature']) or $_COOKIE['oc_session_signature']!=$sSystemSessionVerion )
		{
			unset($_COOKIE[session_name()]) ;
			setcookie('oc_session_signature',$sSystemSessionVerion,time()+315360000) ;
		}
	}

	public function sessionVerion()
	{
		$sSrc = 'framework:' . \org\jecat\framework\VERSION . '/'
					. 'platform:' . Platform::version . '/' ;
		foreach(Service::singleton()->extensions()->enableExtensionMetainfoIterator() as $aExtMeta)
		{
			$sSrc.= 'extension:'.$aExtMeta->name().':'.$aExtMeta->version()->__toString().'/' ;
		}
		
		return md5($sSrc) ;
	}
}

?>