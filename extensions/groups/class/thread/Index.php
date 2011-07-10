<?php
namespace oc\ext\groups\thread ;

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
class Index extends Controller
{
	protected function init()
	{
		// 网页框架
		$this->add(new FrontFrame()) ;
		
		$this->createView("defaultView", "thread.index.html") ;
		
		$this->model = Model::fromFragment('thread',array(),true);
		
		//设置model
		$this->defaultView->setModel($this->model) ;
		
	}
	
	public function process()
	{
		$this->model->load(IdManager::fromSession()->currentId()->userId(),"uid");
		
//		$this->model->printStruct() ;
//		foreach ($this->model->childIterator() as $row){
//			echo "<pre>";print_r($row->data("text"));echo "</pre>";
//		}
	}
}

?>