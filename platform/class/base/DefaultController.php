<?php
namespace oc\base ;

use jc\mvc\controller\Controller;

class DefaultController extends Controller
{
	protected function init()
	{
		$this->createView("defaultView", "Welcome.template.html") ;
	}
}

?>