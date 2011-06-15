<?php
namespace oc ;

use jc\mvc\controller\Controller;

class DefaultController extends Controller
{
	protected function init()
	{
		$this->createView("defaultView", "Welcome.template.html") ;
	}
	
	public function process()
	{
		$this->defaultView->render() ;
	}
}

?>