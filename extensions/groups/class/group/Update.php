<?php
namespace oc\ext\groups\group ;


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
use jc\verifier\NotEmpty;
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
class Update extends Controller
{
	protected function init()
	{
		// 网页框架
		$this->add(new FrontFrame()) ;

		//创建视图
		$this->createView("defaultView", "group.update.html",true) ;
		
		// 为视图创建控件
		$this->defaultView->addWidget( new Text("name","名称","",Text::single), 'name' )->addVerifier( NotEmpty::singleton (), "请说点什么" ) ;
		
		$this->defaultView->addWidget ( new Select ( 'type', '选择类型', 1 ), 'type' )
								->addOption ( "请选择", null, true)
								->addOption ( "同学", "tx" )
								->addOption ( "师生", "ss" )
								->addOption ( "社会", "sh" )
								->addVerifier( NotEmpty::singleton (), "请选择类型" ) ;
						
						
		$this->model = Model::fromFragment('group');
		
		//设置model
		$this->defaultView->setModel($this->model) ;
		
	}
	
	public function process()
	{
		$this->defaultView->model()->load($this->aParams->get("gid"),"gid");
		
		$this->defaultView->exchangeData(DataExchanger::MODEL_TO_WIDGET) ;
		
		$this->defaultView->model()->setData('time',time()) ;
				
		if( $this->defaultView->isSubmit( $this->aParams ) )		 
		{
            // 加载 视图窗体的数据
            $this->defaultView->loadWidgets( $this->aParams ) ;
            
            // 校验 视图窗体的数据
            if( $this->defaultView->verifyWidgets() )
            {
            	$this->defaultView->exchangeData(DataExchanger::WIDGET_TO_MODEL) ;
            	
            	try {
            		if( $this->defaultView->model()->save() )
            		{
	            		$this->defaultView->createMessage( Message::success, "修改成功！" ) ;
	            		$this->defaultView->hideForm() ;
            		}
            		
            		else 
            		{
	            		$this->defaultView->createMessage( Message::success, "修改成功！" ) ;
            		}
            			
            	} catch (ExecuteException $e) {
            			throw $e ;
            	}
           	}
		}
	}
}

?>