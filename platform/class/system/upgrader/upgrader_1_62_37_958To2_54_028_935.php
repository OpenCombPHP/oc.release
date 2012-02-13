<?php
namespace org\opencomb\platform\system\upgrader ;

use org\jecat\framework\message\MessageQueue ;
use org\jecat\framework\message\Message ;

class upgrader_1_62_37_958To2_54_028_935 implements IUpgrader{
	public function process(MessageQueue $aMessageQueue){
		$aMessageQueue->create(
			Message::success,
			__CLASS__
		);
	}
}
