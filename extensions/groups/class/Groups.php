<?php
namespace oc\ext\groups ;

use jc\auth\IdManager;

use jc\mvc\model\db\orm\PrototypeAssociationMap;

use jc\db\DB ;
use jc\db\PDODriver ;

use oc\ext\Extension;

class Groups extends Extension
{
	public function load()
	{
		
    	// 取得模型关系图的单件实例
        $aAssocMap = PrototypeAssociationMap::singleton() ;
    	$aAssocMap->addOrm(
                	array(
                		'keys' => 'gid' ,
                		'table' => 'group' ,
                		'hasMany' => array(
							array(
                				'prop' => 'thread' ,
                				'fromk' => 'gid' ,
                				'tok' => 'gid' ,
                				'model' => 'thread'
							) ,
							array(
                				'prop' => 'user' ,
                				'fromk' => 'gid' ,
                				'tok' => 'gid' ,
                				'model' => 'user'
							) ,
						),
                	)
        ) ;

        
    	$aAssocMap->addOrm(
                	array(
                		'keys' => 'tid' ,
                		'table' => 'thread' ,
                		'hasOne' => array(
							array(
                				'prop' => 'group' ,
                				'fromk' => 'gid' ,
                				'tok' => 'gid' ,
                				'model' => 'group'
							) ,
							array(
                				'prop' => 'poll' ,
                				'fromk' => 'tid' ,
                				'tok' => 'tid' ,
                				'model' => 'poll'
							) ,
						),
						
                	)
        ) ;
        
    	$aAssocMap->addOrm(
                	array(
                		'keys' => 'gid' ,
                		'table' => 'user',
                		'hasOne' => array(
							array(
                				'prop' => 'group' ,
                				'fromk' => 'gid' ,
                				'tok' => 'gid' ,
                				'model' => 'group'
							) ,
						),
                	)
        ) ;
    	$aAssocMap->addOrm(
                	array(
                		'keys' => 'tid' ,
                		'table' => 'poll',
						'hasMany' => array(
							array(
                				'prop' => 'item' ,
                				'fromk' => 'tid' ,
                				'tok' => 'tid' ,
                				'model' => 'poll_item'
							) ,
						),
                	)
        ) ;
    	$aAssocMap->addOrm(
                	array(
                		'keys' => 'iid' ,
                		'table' => 'poll_item',
                	)
        ) ;
        
		///////////////////////////////////////
		// 向系统添加控制器
		$this->application()->accessRouter()->addController("oc\\ext\\groups\\group\\Index",'index') ;
		$this->application()->accessRouter()->addController("oc\\ext\\groups\\group\\Add",'add') ;
		$this->application()->accessRouter()->addController("oc\\ext\\groups\\group\\Update",'update') ;
		$this->application()->accessRouter()->addController("oc\\ext\\groups\\group\\Delete",'delete') ;
		$this->application()->accessRouter()->addController("oc\\ext\\groups\\group\\AddGroup",'addgroup') ;
		$this->application()->accessRouter()->addController("oc\\ext\\groups\\thread\\Index",'thread.index') ;
		$this->application()->accessRouter()->addController("oc\\ext\\groups\\thread\\Add",'thread.add') ;
		$this->application()->accessRouter()->addController("oc\\ext\\groups\\thread\\Update",'thread.update') ;
		$this->application()->accessRouter()->addController("oc\\ext\\groups\\thread\\Delete",'thread.delete') ;
	}
	
}

?>