<?php
namespace oc\mvc\controller ;

class PermissionDenied extends Controller
{
	protected function init()
	{
		$this->createView("view","oc:viewPermissionDenied.template.php") ;
	}

	public function process()
	{}
	
}

?>