<?php
namespace org\opencomb\platform\ext ;

use org\jecat\framework\util\Version;
use org\jecat\framework\message\MessageQueue;
use org\opencomb\platform\ext\ExtensionMetainfo;

interface IExtensionDataUpgrader{
	public function upgrade(MessageQueue $aMessageQueue,Version $aFromVersion , ExtensionMetainfo $aMetainfo);
}
