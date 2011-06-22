<?php
namespace oc\ext\developtoolbox ;

use jc\mvc\controller\Controller;

class Index extends Controller
{
	protected function init()
	{
		$this->createView('view','developtoolbox_Index.template.html') ;
	}
	
	public function process()
	{
		$this->view->render() ;
	}
}

?>