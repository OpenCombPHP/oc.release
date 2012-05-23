<?php
namespace org\opencomb\platform\service\upgrader ;

use org\jecat\framework\message\MessageQueue;
use org\jecat\framework\message\Message;
use org\jecat\framework\util\Version;

/**
 * 为了方便测试的一个抽象基类
 */
class AbstractTestUpgrader implements IUpgrader{
	
	/**
	 * 仅仅输出 开始版本 => 结束版本 ，
	 * 没有任何实际操作
	 */
	public function process(MessageQueue $aMessageQueue){
		$sClass = get_class($this);
		if(preg_match('`^'.preg_quote(__NAMESPACE__).'\\\\upgrader_(\d+((_\d+){0,3}))To(\d+((_\d+){0,3}))$`' , $sClass , $arrMatch)){
			$sClassFromVersion = str_replace('_','.',$arrMatch[1]) ;
			$sClassToVersion = str_replace('_','.',$arrMatch[4]) ;
			
			$aClassFromVersion = Version::fromString($sClassFromVersion);
			$aClassToVersion = Version::fromString($sClassToVersion);
			
			$aMessageQueue->create(
				Message::success,
				'%s => %s',
				array(
					$aClassFromVersion,
					$aClassToVersion
				)
			);
		}else{
			$aMessageQueue->create(
				Message::success,
				'illegal class name:%s',
				$sClass
			);
		}
	}
}


