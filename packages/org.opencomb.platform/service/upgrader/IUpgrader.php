<?php
namespace org\opencomb\platform\service\upgrader ;

use org\jecat\framework\message\MessageQueue;

interface IUpgrader 
{
	public function process(MessageQueue $aMessageQueue) ; 
}



