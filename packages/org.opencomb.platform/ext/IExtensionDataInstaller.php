<?php
namespace org\opencomb\platform\ext ;

use org\jecat\framework\message\MessageQueue;
use org\opencomb\platform\ext\ExtensionMetainfo;

interface IExtensionDataInstaller{
	public function install(MessageQueue $aMessageQueue,ExtensionMetainfo $aMetainfo);
}

