<?php
namespace org\opencomb\platform\mvc\controller ;

use org\jecat\framework\mvc\controller\Controller;

class DefaultController extends Controller
{
	protected $arrConfig = array(
		'title' => 'Welcome to use OpenComb' ,
		'view' => "org.opencomb.platform:mvc/controller/DefaultController.html" ,
	) ;
}

