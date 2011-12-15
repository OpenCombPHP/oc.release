<?php
namespace org\opencomb\platform\mvc\controller ;

use org\jecat\framework\mvc\controller\Controller;

class DefaultController extends Controller
{
	public function createBeanConfig()
	{
		return array(
			'view:welcome' => array(
				'template' => "org.opencomb:Welcome.template.html" ,
			) ,
		) ;
	}
}

?>