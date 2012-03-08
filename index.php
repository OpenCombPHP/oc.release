<?php
namespace org\opencomb\platform ;


use org\jecat\framework\auth\IdManager;

use org\jecat\framework\pattern\serialize\ShareObjectSerializer;

use org\jecat\framework\bean\BeanFactory;
use org\jecat\framework\mvc\model\db\Model;

// 检查系统关闭锁
if( is_file(__DIR__.'/lock.shutdown.html') )
{
	// 检查”后门“密钥，方便管理员进入
	if( empty($_REQUEST['shutdown_backdoor_secret_key']) or !is_file(__DIR__.'/lock.shutdown.backdoor.php') or include(__DIR__.'/lock.shutdown.backdoor.php')!=$_REQUEST['shutdown_backdoor_secret_key'] )
	{
		// ”后门密钥“检查失败，关闭系统
		include __DIR__.'/lock.shutdown.html' ;
		exit() ;
	}
}

// 初始化 jcat 框架
use org\jecat\framework\mvc\controller\Request;
use org\jecat\framework\system\AccessRouter;
use org\jecat\framework\lang\oop\ClassLoader;
use org\jecat\framework\fs\imp\LocalFileSystem;
use org\jecat\framework\fs\File;
use org\jecat\framework\mvc\model\db\orm\PrototypeAssociationMap;
use org\opencomb\platform\mvc\model\db\orm\PAMap;
use org\opencomb\platform\ext\ExtensionMetainfo;
use org\opencomb\platform\system\upgrader\PlatformDataUpgrader ;


$t = microtime(1) ;


// 简单配置启动 OC platform,以及扩展, 以后完善
$aPlatform = require 'jc.init.php' ;
$fPlatformInitTime = microtime(1) - $t ;

$aDataUpgrader = PlatformDataUpgrader::singleton() ; 
if(TRUE === $aDataUpgrader->process()){
	$aDataUpgrader->relocation();
	exit();
}


// unserialize('a:2:{s:6:"arrIds";a:1:{i:34297;C:31:"org\opencomb\coresystem\auth\Id":8511:{a:1:{s:5:"model";C:38:"org\jecat\framework\mvc\model\db\Model":8440:{a:12:{s:32:"000000004bc2c42800000000762f21f2";a:2:{s:5:"class";s:38:"org\jecat\framework\mvc\model\db\Model";s:5:"props";a:2:{s:43:"org\jecat\framework\mvc\model\AbstractModel";a:3:{s:8:"arrDatas";a:12:{s:3:"uid";s:5:"34297";s:8:"username";s:7:"alee201";s:8:"password";s:32:"77b0f2f805bafd9e45861ad14cc403a2";s:5:"email";s:16:"aleechou@163.com";s:4:"myid";s:0:"";s:7:"myidkey";s:0:"";s:5:"regip";s:14:"114.218.110.17";s:7:"regdate";s:10:"1244027412";s:11:"lastloginip";s:9:"127.0.0.1";s:13:"lastlogintime";i:1330308347;s:4:"salt";s:6:"4d0333";s:7:"secques";s:0:"";}s:11:"arrChildren";a:1:{s:4:"info";s:40:"~objid~:000000004bc2c43b00000000762f21f2";}s:10:"arrChanged";a:2:{s:13:"lastlogintime";s:13:"lastlogintime";s:11:"lastloginip";s:11:"lastloginip";}}s:38:"org\jecat\framework\mvc\model\db\Model";a:1:{s:10:"aPrototype";s:40:"~objid~:000000004bc2c46e00000000762f21f2";}}}s:32:"000000004bc2c43b00000000762f21f2";a:2:{s:5:"class";s:56:"org\jecat\framework\mvc\model\db\imp\_myspace_spacefield";s:5:"props";a:2:{s:43:"org\jecat\framework\mvc\model\AbstractModel";a:3:{s:8:"arrDatas";a:32:{s:3:"uid";s:5:"34297";s:3:"sex";s:1:"0";s:8:"nickname";s:0:"";s:5:"email";s:16:"aleechou@163.com";s:10:"emailcheck";s:1:"0";s:2:"qq";s:0:"";s:3:"msn";s:0:"";s:9:"birthyear";s:1:"0";s:10:"birthmonth";s:1:"0";s:8:"birthday";s:1:"0";s:5:"blood";s:0:"";s:5:"marry";s:1:"0";s:13:"birthprovince";s:0:"";s:9:"birthcity";s:0:"";s:14:"resideprovince";s:0:"";s:10:"residecity";s:0:"";s:4:"note";s:3:"林";s:9:"spacenote";s:4:"ffff";s:7:"authstr";s:0:"";s:5:"theme";s:0:"";s:5:"nocss";s:1:"0";s:3:"css";s:0:"";s:7:"privacy";s:0:"";s:6:"friend";s:23:"34374,34315,34366,34317";s:10:"feedfriend";s:23:"34374,34315,34366,34317";s:8:"sendmail";s:0:"";s:7:"setting";s:0:"";s:7:"field_1";s:0:"";s:7:"menunum";s:1:"0";s:7:"field_3";s:0:"";s:11:"achievement";s:2:"70";s:6:"avatar";s:0:"";}s:11:"arrChildren";a:0:{}s:10:"arrChanged";a:0:{}}s:38:"org\jecat\framework\mvc\model\db\Model";a:1:{s:10:"aPrototype";s:40:"~objid~:000000004bc2c40100000000762f21f2";}}}s:32:"000000004bc2c40100000000762f21f2";a:2:{s:5:"class";s:63:"org\jecat\framework\mvc\model\db\prototype\_coresystem_userinfo";s:5:"props";a:1:{s:46:"org\jecat\framework\mvc\model\db\orm\Prototype";a:11:{s:5:"sName";s:4:"info";s:10:"sTableName";s:18:"myspace_spacefield";s:10:"arrColumns";a:32:{i:0;s:3:"uid";i:1;s:3:"sex";i:2;s:8:"nickname";i:3;s:5:"email";i:4;s:10:"emailcheck";i:5;s:2:"qq";i:6;s:3:"msn";i:7;s:9:"birthyear";i:8;s:10:"birthmonth";i:9;s:8:"birthday";i:10;s:5:"blood";i:11;s:5:"marry";i:12;s:13:"birthprovince";i:13;s:9:"birthcity";i:14;s:14:"resideprovince";i:15;s:10:"residecity";i:16;s:4:"note";i:17;s:9:"spacenote";i:18;s:7:"authstr";i:19;s:5:"theme";i:20;s:5:"nocss";i:21;s:3:"css";i:22;s:7:"privacy";i:23;s:6:"friend";i:24;s:10:"feedfriend";i:25;s:8:"sendmail";i:26;s:7:"setting";i:27;s:7:"field_1";i:28;s:7:"menunum";i:29;s:7:"field_3";i:30;s:11:"achievement";i:31;s:6:"avatar";}s:16:"arrColumnAliases";a:1:{s:8:"nickname";N;}s:7:"arrKeys";a:1:{i:0;s:3:"uid";}s:17:"sDevicePrimaryKey";N;s:11:"sModelClass";s:56:"org\jecat\framework\mvc\model\db\imp\_myspace_spacefield";s:9:"aCriteria";s:40:"~objid~:000000004bc2c41500000000762f21f2";s:14:"aAssociationBy";s:40:"~objid~:000000004bc2c41200000000762f21f2";s:15:"arrAssociations";a:0:{}s:13:"arrBeanConfig";a:8:{s:5:"table";s:18:"myspace_spacefield";s:4:"type";i:1;s:4:"name";s:4:"info";s:5:"class";s:48:"org\opencomb\platform\mvc\model\db\orm\Prototype";s:13:"fromPrototype";s:40:"~objid~:000000004bc2c46e00000000762f21f2";s:12:"tableTransed";b:1;s:17:"disableTableTrans";b:1;s:5:"alias";a:1:{s:8:"nickname";N;}}}}}s:32:"000000004bc2c41500000000762f21f2";a:2:{s:5:"class";s:35:"org\jecat\framework\db\sql\Criteria";s:5:"props";a:3:{s:35:"org\jecat\framework\db\sql\Criteria";a:5:{s:6:"aWhere";N;s:6:"aOrder";N;s:10:"sLimitFrom";i:0;s:9:"nLimitLen";i:30;s:14:"arrGroupByClms";N;}s:36:"org\jecat\framework\db\sql\Statement";a:2:{s:13:"aNameTransfer";s:40:"~objid~:000000004bc2c41f00000000762f21f2";s:17:"aStatementFactory";s:40:"~objid~:000000004bc2c40100000000762f21f2";}s:31:"org\jecat\framework\lang\Object";a:1:{s:11:"aProperties";N;}}}s:32:"000000004bc2c41f00000000762f21f2";a:2:{s:5:"class";s:44:"org\jecat\framework\db\sql\name\NameTransfer";s:5:"props";a:1:{s:44:"org\jecat\framework\db\sql\name\NameTransfer";a:2:{s:17:"aColumnNameFilter";s:40:"~objid~:000000004bc2c41b00000000762f21f2";s:16:"aTableNameFilter";N;}}}s:32:"000000004bc2c41b00000000762f21f2";a:2:{s:5:"class";s:39:"org\jecat\framework\util\FilterMangeger";s:5:"props";a:2:{s:39:"org\jecat\framework\util\FilterMangeger";a:2:{s:10:"arrFilters";a:1:{i:0;a:2:{i:0;a:2:{i:0;s:40:"~objid~:000000004bc2c40100000000762f21f2";i:1;s:25:"statementColumnNameHandle";}i:1;a:0:{}}}s:8:"bWorking";b:1;}s:31:"org\jecat\framework\lang\Object";a:1:{s:11:"aProperties";N;}}}s:32:"000000004bc2c41200000000762f21f2";a:2:{s:5:"class";s:50:"org\opencomb\platform\mvc\model\db\orm\Association";s:5:"props";a:1:{s:48:"org\jecat\framework\mvc\model\db\orm\Association";a:9:{s:5:"nType";i:1;s:11:"arrFromKeys";a:1:{i:0;s:3:"uid";}s:9:"arrToKeys";a:1:{i:0;s:3:"uid";}s:12:"sBridgeTable";N;s:15:"arrToBridgeKeys";a:0:{}s:17:"arrFromBridgeKeys";a:0:{}s:9:"sJoinType";s:9:"LEFT JOIN";s:13:"arrBeanConfig";a:5:{s:5:"table";s:8:"userinfo";s:4:"type";i:1;s:4:"name";s:4:"info";s:5:"class";s:50:"org\opencomb\platform\mvc\model\db\orm\Association";s:13:"fromPrototype";s:40:"~objid~:000000004bc2c46e00000000762f21f2";}s:12:"aToPrototype";s:40:"~objid~:000000004bc2c40100000000762f21f2";}}}s:32:"000000004bc2c46e00000000762f21f2";a:2:{s:5:"class";s:59:"org\jecat\framework\mvc\model\db\prototype\_coresystem_user";s:5:"props";a:1:{s:46:"org\jecat\framework\mvc\model\db\orm\Prototype";a:11:{s:5:"sName";s:4:"user";s:10:"sTableName";s:13:"wower_members";s:10:"arrColumns";a:12:{i:0;s:3:"uid";i:1;s:8:"username";i:2;s:8:"password";i:3;s:5:"email";i:4;s:4:"myid";i:5;s:7:"myidkey";i:6;s:5:"regip";i:7;s:7:"regdate";i:8;s:11:"lastloginip";i:9;s:13:"lastlogintime";i:10;s:4:"salt";i:11;s:7:"secques";}s:16:"arrColumnAliases";a:6:{s:12:"registerTime";s:7:"regdate";s:10:"registerIp";s:5:"regip";s:13:"lastLoginTime";s:13:"lastlogintime";s:11:"lastLoginIp";s:11:"lastloginip";s:10:"activeTime";N;s:8:"activeIp";N;}s:7:"arrKeys";a:1:{i:0;s:3:"uid";}s:17:"sDevicePrimaryKey";N;s:11:"sModelClass";s:38:"org\jecat\framework\mvc\model\db\Model";s:9:"aCriteria";s:40:"~objid~:000000004bc2c46a00000000762f21f2";s:14:"aAssociationBy";N;s:15:"arrAssociations";a:1:{i:0;s:40:"~objid~:000000004bc2c41200000000762f21f2";}s:13:"arrBeanConfig";a:8:{s:5:"table";s:13:"wower_members";s:11:"hasOne:info";a:4:{s:5:"table";s:8:"userinfo";s:4:"type";i:1;s:4:"name";s:4:"info";s:5:"class";s:50:"org\opencomb\platform\mvc\model\db\orm\Association";}s:4:"name";s:4:"user";s:5:"class";s:48:"org\opencomb\platform\mvc\model\db\orm\Prototype";s:11:"model-class";s:38:"org\jecat\framework\mvc\model\db\Model";s:12:"tableTransed";b:1;s:17:"disableTableTrans";b:1;s:5:"alias";a:6:{s:12:"registerTime";s:7:"regdate";s:10:"registerIp";s:5:"regip";s:13:"lastLoginTime";s:13:"lastlogintime";s:11:"lastLoginIp";s:11:"lastloginip";s:10:"activeTime";N;s:8:"activeIp";N;}}}}}s:32:"000000004bc2c46a00000000762f21f2";a:2:{s:5:"class";s:35:"org\jecat\framework\db\sql\Criteria";s:5:"props";a:3:{s:35:"org\jecat\framework\db\sql\Criteria";a:5:{s:6:"aWhere";N;s:6:"aOrder";N;s:10:"sLimitFrom";i:0;s:9:"nLimitLen";i:30;s:14:"arrGroupByClms";N;}s:36:"org\jecat\framework\db\sql\Statement";a:2:{s:13:"aNameTransfer";s:40:"~objid~:000000004bc2c47400000000762f21f2";s:17:"aStatementFactory";s:40:"~objid~:000000004bc2c46e00000000762f21f2";}s:31:"org\jecat\framework\lang\Object";a:1:{s:11:"aProperties";N;}}}s:32:"000000004bc2c47400000000762f21f2";a:2:{s:5:"class";s:44:"org\jecat\framework\db\sql\name\NameTransfer";s:5:"props";a:1:{s:44:"org\jecat\framework\db\sql\name\NameTransfer";a:2:{s:17:"aColumnNameFilter";s:40:"~objid~:000000004bc2c47700000000762f21f2";s:16:"aTableNameFilter";N;}}}s:32:"000000004bc2c47700000000762f21f2";a:2:{s:5:"class";s:39:"org\jecat\framework\util\FilterMangeger";s:5:"props";a:2:{s:39:"org\jecat\framework\util\FilterMangeger";a:2:{s:10:"arrFilters";a:1:{i:0;a:2:{i:0;a:2:{i:0;s:40:"~objid~:000000004bc2c46e00000000762f21f2";i:1;s:25:"statementColumnNameHandle";}i:1;a:0:{}}}s:8:"bWorking";b:1;}s:31:"org\jecat\framework\lang\Object";a:1:{s:11:"aProperties";N;}}}s:4:"root";s:40:"~objid~:000000004bc2c42800000000762f21f2";}}}}}s:13:"sCurrentIdUid";s:5:"34297";}') ;


/*
$aModelList = BeanFactory::singleton()->createBean($a=array(
		'class' => 'model' ,
		'list' => true ,
		'orm' => array(
				'table' => 'book' ,
				
				'hasMany:bookcomments' => array(
					'table' => 'frameworktest:bookcomment' ,
					'tokeys' => 'bid' ,
					'fromkeys' => 'bid' ,
				) ,
		) ,
),'frameworktest') ;
$aModelList->createChild() ;
	

$s = serialize($aModelList) ;
$aModelList2 = unserialize($s) ;

$b = $aModelList->prototype() === $aModelList->child(0)->prototype() ;
// $s2 = ShareObjectSerializer::singleton()->serialize($aModel2) ;

echo $s, "\r\n" ;
echo $s2, "\r\n" ;
echo $s == $s2 ? '111111111': '0000000000' ;


// $aModel2 = unserialize( serialize($aModel) ) ;
exit() ;
*/

// 根据路由设置创建控制器 并 执行
$aController = AccessRouter::singleton()->createRequestController(Request::singleton()) ;
if($aController)
{
	$aController->mainRun() ;
}
else 
{
	header("HTTP/1.0 404 Not Found");
	echo "<h1>Page Not Found</h1>" ;
}

if(empty($_REQUEST['rspn'])){
	//echo $aPlatform->signature() ;
	echo 'total: ', $aPlatform->uptime(true),'<br />' ;
	echo 'platform init: ', $fPlatformInitTime,'<br />' ;
	echo 'class load: ', ClassLoader::singleton()->totalLoadTime() ;
}
