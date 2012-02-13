<?php
namespace org\opencomb\platform\system\upgrader ;

use org\jecat\framework\message\MessageQueue ;
use org\jecat\framework\message\Message ;

class upgrader_0_0_0_0To0_1_2_3 implements IUpgrader{
	public function process(MessageQueue $aMessageQueue){
		$aMessageQueue->create(
			Message::success,
			'0.0.0.0=>0.1.2.3'
		);
	}
}
