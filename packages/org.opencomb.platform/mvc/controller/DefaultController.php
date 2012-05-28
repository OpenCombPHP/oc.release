<?php
namespace org\opencomb\platform\mvc\controller ;

use org\jecat\framework\mvc\controller\Controller;

class DefaultController extends Controller
{
	public function createBeanConfig()
	{
		return array(
			
			'title' => 'Welcome to use OpenComb' ,
				
			'view:welcome' => array(
				'template' => "org.opencomb.platform:Welcome.template.html" ,
			) ,
		) ;
	}
}

