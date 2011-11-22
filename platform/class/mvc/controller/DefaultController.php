<?php
namespace oc\mvc\controller ;

use jc\mvc\controller\Controller;

class DefaultController extends Controller
{
	protected function init()
	{
		$this->createView("defaultView", "oc:Welcome.template.html") ;
	}
}

?>