<?php
namespace oc\ext\groups\thread ;

use jc\verifier\NotEmpty;

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
class Add extends Controller
{
	protected function init()
	{
		// 网页框架
		$this->add(new FrontFrame()) ;
		
		
		$this->createView("defaultView", "thread.add.html",true) ;
		
		// 为视图创建控件
		$this->defaultView->addWidget( new Text("content","群组","",Text::multiple), 'content' )->addVerifier( NotEmpty::singleton (), "请说点什么" ) ;
		
		$this->oSelect = new Select ( 'group', '选择类型', 1 );
		$this->oSelect->addOption ( "请选择", null, true) ;
		$this->oSelect->addVerifier( NotEmpty::singleton (), "请选择类型" );
		
		$this->defaultView->addWidget ( $this->oSelect, 'gid' );
						
						
		$this->model = Model::fromFragment('thread');
		
		//设置model
		$this->defaultView->setModel($this->model) ;
		
	}
	
	public function process()
	{
		
		$oUserModel = Model::fromFragment('user',array("group"),true);
		$oUserModel->load(IdManager::fromSession()->currentId()->userId(),"uid");
		//$oUserModel->printStruct();
		
		foreach ($oUserModel->childIterator() as $row){
			$this->oSelect	->addOption ($row->child("group")->data("name"),$row->child("group")->data("gid"));
		}
		
		
		if( $this->defaultView->isSubmit( $this->aParams ) )
		{
            // 加载 视图窗体的数据
            $this->defaultView->loadWidgets( $this->aParams ) ;
            
            // 校验 视图窗体的数据
            if( $this->defaultView->verifyWidgets() )
            {
            	$this->defaultView->exchangeData(DataExchanger::WIDGET_TO_MODEL) ;
            	
				$this->defaultView->model()->setData('uid',IdManager::fromSession()->currentId()->userId()) ;
				$this->defaultView->model()->setData('time',time()) ;
				
            	try {
            		if( $this->defaultView->model()->save() )
            		{
            			$this->defaultView->createMessage( Message::success, "发布成功！" ) ;
            			$this->defaultView->hideForm() ;
            		}
            		else 
            		{
            			$this->defaultView->createMessage( Message::failed, "遇到错误！" ) ;
            		}
            		
            			
            	} catch (ExecuteException $e) {
            			throw $e ;
            	}
           	}
		}
	}
}

?>