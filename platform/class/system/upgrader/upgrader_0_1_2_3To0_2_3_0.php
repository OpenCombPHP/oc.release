<?php
namespace org\opencomb\platform\system\upgrader ;

use org\jecat\framework\message\MessageQueue ;
use org\jecat\framework\message\Message ;

class upgrader_0_1_2_3To0_2_3_0 implements IUpgrader{
	public function process(MessageQueue $aMessageQueue){
		$aMessageQueue->create(
			Message::success,
			'0.1.2.3=>0.2.3.0'
		);
	}
}
