<?php
namespace oc\base ;

use jc\mvc\controller\WebpageFrame;
use jc\mvc\view\View;

class FrontFrame extends WebpageFrame
{
	public function __construct()
	{
		parent::__construct() ;
		
		$this->frameView = new View('frameView',"oc_FrontFrame.template.html") ;
		$this->addFrameView( $this->frameView ) ;
	}
	
	private $frameView ;
}

?>