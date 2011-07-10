<?php
namespace oc\ext\groups\group ;

use jc\mvc\controller\Relocater;

use oc\base\FrontFrame;

use jc\session\Session;
use jc\auth\IdManager;
use jc\auth\Id;
use jc\db\ExecuteException;
use oc\mvc\controller\Controller ;
use oc\mvc\model\db\Model;
use jc\mvc\model\db\orm\PrototypeAssociationMap;
use jc\verifier\Email;
use jc\verifier\Length;
use jc\verifier\NotNull;
use jc\mvc\view\widget\Text;
use jc\mvc\view\widget\Select;
use jc\mvc\view\widget\CheckBtn;
use jc\mvc\view\widget\RadioGroup;
use jc\message\Message ;
use jc\mvc\view\DataExchanger ;


/**
 * Enter description here ...
 * @author gaojun
 *
 */
class Delete extends Controller
{
	protected function init()
	{
		// 网页框架
		$this->add(new FrontFrame()) ;
		
		$this->model = Model::fromFragment('group');
	}
	
	public function process()
	{
		
		$this->model->load($this->aParams->get("gid"),"gid");
		if($this->model->delete())
		{
		    Relocater::locate("/?c=groups.index", "删除成功") ;
		}
	}
}

?>