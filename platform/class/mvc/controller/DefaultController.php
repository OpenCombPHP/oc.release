<?php
namespace org\opencomb\mvc\controller ;

use org\jecat\framework\mvc\controller\Controller;

class DefaultController extends Controller
{
	protected function init()
	{
		$this->createView("defaultView", "oc:Welcome.template.html") ;
	}
}

?>