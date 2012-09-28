<?php
namespace org\opencomb\platform\service\upgrader ;

use org\jecat\framework\message\MessageQueue;
use org\jecat\framework\message\Message;
use org\jecat\framework\util\Version;
use org\opencomb\platform\service\Service;
use org\opencomb\platform\service\ServiceFactory;
use org\jecat\framework\setting\Setting;

class upgrader_0_3_1To0_3_2 implements IUpgrader{
	public function process(MessageQueue $aMessageQueue){
		$aService = Service::singleton();
		
		$arrServiceSetting = &$aService->serviceSetting();
		
		if( $arrServiceSetting['serviceSetting']['type'] === ServiceFactory::FS_SETTING ){
			$aServiceFactory = ServiceFactory::singleton();
			$aFormerFsSetting = $aServiceFactory->createServiceSetting(
				array(
					'type' => ServiceFactory::FS_SETTING
				)
			);
			
			$arrKeyList = $aFormerFsSetting->keyList('');
			
			$aScalableSetting = $aServiceFactory->createServiceSetting(
				array(
					'type' => ServiceFactory::SCALABLE_SETTING,
					'innerSetting' => array(
						'type' => ServiceFactory::FS_SETTING
					),
				)
			);
			
			foreach($arrKeyList as $sKey){
				$aScalableSetting->setValue(
					$sKey,
					$aFormerFsSetting->value($sKey,null)
				);
				$aMessageQueue->create(
					Message::success,
					'将Setting中的`%s`升级到最新版本',
					array(
						$sKey
					)
				);
			}
			
			$arrServiceSetting['serviceSetting'] = 
			array (
				'type' => 'SCALABLE_SETTING',
				'innerSetting' => 
				array (
					'type' => 'FS_SETTING',
				),
			);
			
			Setting::setSingleton( $aScalableSetting );
		}
	}
}
