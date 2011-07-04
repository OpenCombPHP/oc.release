<?php
namespace oc\base ;

use jc\mvc\controller\Controller;

class DefaultController extends Controller
{
	protected function init()
	{
		$this->add( new FrontFrame() ) ;
		
		$this->createView("defaultView", "oc:Welcome.template.html") ;
	}
}

?>