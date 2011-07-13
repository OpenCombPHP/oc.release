<?php
namespace oc\ext\groups\thread ;

use jc\mvc\controller\Relocater;

use oc\mvc\view\View;

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
		$this->createView("defaultView", "thread.update.html",true) ;
		
		
		
		
		// 为视图创建控件
		$this->defaultView->addWidget( new Text("content","群组","",Text::multiple), 'content' )->addVerifier( NotEmpty::singleton (), "请说点什么" ) ;
		
		$this->oSelect = new Select ( 'group', '选择类型', 1 );
		$this->oSelect->addOption ( "请选择", null, true) ;
		$this->oSelect->addVerifier( NotEmpty::singleton (), "请选择类型" );
		
		$this->defaultView->addWidget ( $this->oSelect, 'gid' );
		
		if($this->aParams->get("t")=="thread")
		{
			$this->model = Model::fromFragment('thread');
			
		}
		elseif ($this->aParams->get("t")=="poll")
		{
			$this->defaultView->add(
				$this->pollView = new View("pollView", "thread.update.poll.html")
			);
			
			
			$this->pollView->addWidget ( new Select ( 'poll_maxitem', '选择数量', 1 ), 'poll.maxitem' )
								->addOption ( "不限制", "0", true)
								->addOption ( "最多2项", "2" )
								->addOption ( "最多3项", "3" )
						->addVerifier( NotEmpty::singleton (), "请选择数量" ) ;
			
			$this->model = Model::fromFragment('thread',array("poll"=>array("item")));
			$this->pollView->setModel($this->model) ;
		}
		
		//设置model
		$this->defaultView->setModel($this->model) ;
		
	}
	
	public function process()
	{
		//群组下拉菜单内容
		$oUserModel = Model::fromFragment('user',array("group"),true);
		$oUserModel->load(IdManager::fromSession()->currentId()->userId(),"uid");
		
		foreach ($oUserModel->childIterator() as $row){
			$this->oSelect	->addOption ($row->child("group")->data("name"),$row->child("group")->data("gid"));
		}
		
		
		$this->defaultView->model()->load($this->aParams->get("tid"),"tid");
		$this->defaultView->exchangeData(DataExchanger::MODEL_TO_WIDGET) ;
		
		if($this->aParams->get("t")=="poll")
		{
			$i=0;
			foreach ($this->defaultView->model()->child("poll")->child("item")->childIterator() as $row)
			{
				if($row["votes"] > 0)
				{
				    $this->defaultView->createMessage( Message::failed, "此投票已经有人参与，不可以修改！" ) ;
				    $this->defaultView->hideForm();
            		$this->pollView->disable();
				}
				$this->pollView->addWidget( new Text("poll_item_title_".$i,"投票内容",$row->data("title"),Text::single), 'item.title' );
				$i++;
			}
		}
		
				
		if( $this->defaultView->isSubmit( $this->aParams ) )		 
		{
            // 加载 视图窗体的数据
            $this->defaultView->loadWidgets( $this->aParams ) ;
            
            // 校验 视图窗体的数据
            if( $this->defaultView->verifyWidgets() )
            {
            	$this->defaultView->exchangeData(DataExchanger::WIDGET_TO_MODEL) ;
				$this->defaultView->model()->setData('time',time()) ;
				
				if($this->aParams->get("t")=="poll")
				{
					$this->defaultView->model()->child('poll')->child('item')->delete();
					$this->defaultView->model()->child('poll')->child('item')->clearChildren() ;
					
					for($i = 0; $i < $this->aParams->get("itemSum"); $i++){
						if($this->aParams->get("poll_item_title_".$i))
						{
						    $item = $this->defaultView->model()->child('poll')->child('item')->createChild();
					    	$item->setData("title",$this->aParams->get("poll_item_title_".$i));
					    	$item->setData("tid",$this->defaultView->model()->data("tid"));
						}
						
					}
				}
				
            	try {
            		if( $this->defaultView->model()->save() )
            		{
            			$this->defaultView->createMessage( Message::failed, "修改成功！" ) ;
            			$this->defaultView->hideForm();
            			$this->pollView->disable();
            			//Relocater::locate("/?c=groups.thread.index", "修改成功！") ;
            		}
            		
            		else 
            		{
            			$this->defaultView->createMessage( Message::failed, "修改失败！" ) ;
            			$this->pollView->disable();
	            		//Relocater::locate("/?c=groups.thread.index", "修改失败！") ;
            		}
            			
            	} catch (ExecuteException $e) {
            			throw $e ;
            	}
           	}
		}
	}
}

?>