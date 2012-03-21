<?php
namespace org\opencomb\platform\system ;

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
		$sSystemSignature = Platform::singleton()->systemSignature() ; 
		if( empty($_COOKIE['oc_session_signature']) or $_COOKIE['oc_session_signature']!=$sSystemSignature )
		{
			unset($_COOKIE[session_name()]) ;
			setcookie('oc_session_signature',$sSystemSignature,time()+315360000) ;
		}
	}
}

?>