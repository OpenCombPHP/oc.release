<?php
namespace oc\ext\groups ;

use jc\auth\IdManager;

use jc\mvc\model\db\orm\ModelAssociationMap;

use jc\db\DB ;
use jc\db\PDODriver ;

use oc\ext\Extension;

class Groups extends Extension
{
	public function load()
	{
		
    	// 取得模型关系图的单件实例
        $aAssocMap = ModelAssociationMap::singleton() ;
    	$aAssocMap->addOrm(
                	array(
                		'keys' => 'mid' ,
                		'table' => 'group' ,
                		'hasMany' => array(
							array(
                				'prop' => 'thread' ,
                				'fromk' => 'mid' ,
                				'tok' => 'mid' ,
                				'model' => 'thread'
							) ,
							array(
                				'prop' => 'user' ,
                				'fromk' => 'mid' ,
                				'tok' => 'mid' ,
                				'model' => 'user'
							) ,
						),
                	)
        ) ;

        
    	$aAssocMap->addOrm(
                	array(
                		'keys' => 'mid' ,
                		'table' => 'thread' ,
                		'hasOne' => array(
							array(
                				'prop' => 'group' ,
                				'fromk' => 'mid' ,
                				'tok' => 'mid' ,
                				'model' => 'group'
							) ,
						),
                	)
        ) ;
        
		///////////////////////////////////////
		// 向系统添加控制器
		$this->application()->accessRouter()->addController("oc\\ext\\groups\\group\\Index",'group',"") ;
		$this->application()->accessRouter()->addController("oc\\ext\\groups\\thread\\Index",'thread',"") ;
	}
	
}

?>