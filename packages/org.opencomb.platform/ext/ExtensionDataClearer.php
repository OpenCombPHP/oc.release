<?php
namespace org\opencomb\platform\ext ;

use org\jecat\framework\cache\Cache;
use org\jecat\framework\db\DB;
use org\jecat\framework\message\Message;
use org\jecat\framework\message\MessageQueue;
use org\opencomb\platform\service\Service;
use org\jecat\framework\lang\Object;

class ExtensionDataClearer extends Object
{
	public function clear(Service $aServce,$sExtName,MessageQueue $aMessageQueue)
	{
		if( !$aExtMeta = $aServce->extensions()->extensionMetainfo($sExtName) )
		{
			return ;
		}
		
		$aOriService = Service::switchSingleton($aServce) ;
		
		$aExtension = new Extension($aExtMeta) ;

		// cache
		if( $aExtension->cache()->clear() )
		{
			$aMessageQueue->create(Message::success,"清理扩展 %s 的缓存数据完毕。",$sExtName) ;
		}
		else 
		{
			$aMessageQueue->create(Message::error,"清理扩展 %s 的缓存数据时遇到了错误。",$sExtName) ;
		}
		
		// setting
		$aExtension->setting()->deleteKey('/') ;
		$aMessageQueue->create(Message::success,"清理扩展 %s 的配置完毕。",$sExtName) ;
		
		// db table
		$aDB = DB::singleton() ;
		$aDbReflecter = $aDB->reflecterFactory()->dbReflecter($aDB->currentDBName());
		foreach( $aDbReflecter->tableNameIterator() as $sTableName )
		{
			if(self::isExtensionTable($sTableName,$sExtName))
			{
				try{
					$aDB->execute('DROP TABLE '.$sTableName) ;
					$aMessageQueue->create(Message::success,"删除了扩展 %s 的数据表: %s。",array($sExtName,$sTableName)) ;
				} catch (\Exception $e) {
					$aMessageQueue->create(Message::error,"删除扩展 %s 的数据表: %s时遇到了错误。",array($sExtName,$sTableName)) ;
				}
			}
		}

		// 清理系统中的数据库缓存
		// 防止在平台管理之外，数据库的结构发生改变
		Cache::singleton()->delete('/db');
		
		
		// data
		foreach( array(
				'数据' => $aExtension->dataFolder() ,
				'临时文件' => $aExtension->tmpFolder() ,
				'上传文件' => $aExtension->filesFolder() ,
		) as $sFolderName=>$aFolder)
		{
			if( $aFolder->delete(true,true) )
			{
				$aMessageQueue->create(Message::success,"清理扩展 %s 的%s目录完毕。",array($sFolderName,$sExtName)) ;
			}
			else 
			{
				$aMessageQueue->create(Message::error,"清理扩展 %s 的%s目录遇到了错误。",array($sFolderName,$sExtName)) ;
			}			
		}
		
		Service::switchSingleton($aOriService) ;
	}
	
	/**
	 * @brief 检查表 \a $sFullTableName 是否是扩展 $sExtName 的数据表
	 * @return boolean
	 * 检查规则: 若 $sFullTableName 以 ${sExtName}_开头，则返回true，否则返回false
	 */
	static public function isExtensionTable($sFullTableName,$sExtName)
	{
		$sTableNamePrefix = DB::singleton()->tableNamePrefix().$sExtName.'_' ;
		return strpos($sFullTableName,$sTableNamePrefix)===0 ;
	}
}
