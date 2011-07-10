<?php
namespace oc\ext\groups\group;

use jc\mvc\controller\Relocater;

use jc\verifier\NotEmpty;

use oc\base\FrontFrame;

use jc\session\Session;
use jc\auth\IdManager;
use jc\auth\Id;
use jc\db\ExecuteException;
use oc\mvc\controller\Controller;
use oc\mvc\model\db\Model;
use jc\mvc\model\db\orm\PrototypeAssociationMap;
use jc\verifier\Email;
use jc\verifier\Length;
use jc\verifier\NotNull;
use jc\mvc\view\widget\Text;
use jc\mvc\view\widget\Select;
use jc\mvc\view\widget\CheckBtn;
use jc\mvc\view\widget\RadioGroup;
use jc\message\Message;
use jc\mvc\view\DataExchanger;

/**
 * Enter description here ...
 * @author gaojun
 *
 */
class AddGroup extends Controller {
	protected function init() {
		// 网页框架
		$this->add ( new FrontFrame () );
		
		//设置model
		$this->model = Model::fromFragment('user');
	
	}
	
	public function process() {
		
		
		if(!$this->model->load(array($this->aParams->get("gid"),IdManager::fromSession ()->currentId ()->userId ()),array("gid","uid")))
		{
			$this->model->setData ( 'uid', IdManager::fromSession ()->currentId ()->userId () );
			$this->model->setData ( 'gid', $this->aParams->get("gid") );
			$this->model->setData ( 'time', time () );
			
			try {
				if ($this->model->save ()) {
					Relocater::locate("/?c=groups.index", "增加成功") ;
				} else {
					Relocater::locate("/?c=groups.index", "增加失败") ;
				}
			
			} catch ( ExecuteException $e ) {
				throw $e;
			}
		}else {
			Relocater::locate("/?c=groups.index", "已经加入过。") ;
		}
		
		
	}
}

?>