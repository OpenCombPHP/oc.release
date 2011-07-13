<?php
namespace oc\ext\groups\thread ;

use jc\mvc\controller\Relocater;

use oc\mvc\view\View;
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
		$this->defaultView->addWidget( new Text("title","标题","",Text::single), 'title' )->addVerifier( NotEmpty::singleton (), "请说点什么" ) ;
		$this->defaultView->addWidget( new Text("content","群组","",Text::multiple), 'content' )->addVerifier( NotEmpty::singleton (), "请说点什么" ) ;
		
		$this->oSelect = new Select ( 'group', '选择类型', 1 );
		$this->oSelect->addOption ( "请选择", null, true) ;
		$this->oSelect->addVerifier( NotEmpty::singleton (), "请选择类型" );
		
		$this->defaultView->addWidget ( $this->oSelect, 'gid' );
			
		if($this->aParams->get("t")=="")
		{
			$this->model = Model::fromFragment('thread');
			
		}
		elseif ($this->aParams->get("t")=="poll")
		{
			$this->defaultView->add(
				$this->pollView = new View("pollView", "thread.add.poll.html")
			);
			
			
			$this->pollView->addWidget ( new Select ( 'poll_maxitem', '选择数量', 1 ), 'poll.maxitem' )
								->addOption ( "不限制", "0", true)
								->addOption ( "最多2项", "2" )
								->addOption ( "最多3项", "3" )
						->addVerifier( NotEmpty::singleton (), "请选择数量" ) ;
			
			for($i = 1; $i <= 5; $i++){
				$this->pollView->addWidget( new Text("poll_item_title_".$i,"投票内容","",Text::single), 'item.title' ) ;
			}
			
			
			$this->model = Model::fromFragment('thread',array("poll"=>array("item")));
			$this->pollView->setModel($this->model) ;
		}
		
		
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
				
				
				if($this->aParams->get("t") == "poll")
				{
					$this->defaultView->model()->setData('type',"poll") ;
					
					for($i = 1; $i <= $this->aParams->get("itemSum"); $i++){
						if($this->aParams->get("poll_item_title_".$i))
						{
						    $item = $this->defaultView->model()->child('poll')->child('item')->createChild();
				    		$item->setData("title",$this->aParams->get("poll_item_title_".$i));
						}
					}
				}else{
					$this->defaultView->model()->setData('type',"thread") ;
				}
				
            	try {
            		if( $this->defaultView->model()->save() )
            		{
            			Relocater::locate("/?c=groups.thread.index", "修改成功！") ;
            		}
            		else 
            		{
            			Relocater::locate("/?c=groups.thread.index", "修改失败！") ;
            		}
            			
            	} catch (ExecuteException $e) {
            			throw $e ;
            	}
           	}
		}
	}
}

?>