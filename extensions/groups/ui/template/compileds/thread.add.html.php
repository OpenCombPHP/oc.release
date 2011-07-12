
<?php 
$__ui_msgqueue = eval("if(!isset(\$__uivar_theView)){ \$__uivar_theView=&\$aVariables->getRef('theView') ;};
return \$__uivar_theView;") ;
if( $__ui_msgqueue instanceof \jc\message\IMessageQueueHolder )
{ $__ui_msgqueue = $__ui_msgqueue->messageQueue() ; }
\jc\lang\Assert::type( '\\jc\\message\\IMessageQueue',$__ui_msgqueue);
if( $__ui_msgqueue->count() ){ 
	$__ui_msgqueue->display($this,$aDevice) ;
} ?>


<?php if( !($aVariables->get('theView') instanceof \jc\mvc\view\FormView) or $aVariables->get('theView')->isShowForm() ) { ?>
<form action="" method="post">
	<div>
		群组：<?php $_aWidget = $aVariables->get('theView')->widget("group") ;
if($_aWidget){
	$_aWidget->display($this,null,$aDevice) ;
}else{
	echo '缺少 widget (id:'."group".')' ;
} ?>

		<?php 
$__ui_msgqueue = eval("if(!isset(\$__uivar_theView)){ \$__uivar_theView=&\$aVariables->getRef('theView') ;};
return \$__uivar_theView->widget('group');") ;
if( $__ui_msgqueue instanceof \jc\message\IMessageQueueHolder )
{ $__ui_msgqueue = $__ui_msgqueue->messageQueue() ; }
\jc\lang\Assert::type( '\\jc\\message\\IMessageQueue',$__ui_msgqueue);
if( $__ui_msgqueue->count() ){ 
	$__ui_msgqueue->display($this,$aDevice) ;
} ?>

	</div>
	<div>
		标题：<?php $_aWidget = $aVariables->get('theView')->widget("title") ;
if($_aWidget){
	$_aWidget->display($this,null,$aDevice) ;
}else{
	echo '缺少 widget (id:'."title".')' ;
} ?>

		<?php 
$__ui_msgqueue = eval("if(!isset(\$__uivar_theView)){ \$__uivar_theView=&\$aVariables->getRef('theView') ;};
return \$__uivar_theView->widget('title');") ;
if( $__ui_msgqueue instanceof \jc\message\IMessageQueueHolder )
{ $__ui_msgqueue = $__ui_msgqueue->messageQueue() ; }
\jc\lang\Assert::type( '\\jc\\message\\IMessageQueue',$__ui_msgqueue);
if( $__ui_msgqueue->count() ){ 
	$__ui_msgqueue->display($this,$aDevice) ;
} ?>

	</div>
	<div>
		内容：<?php $_aWidget = $aVariables->get('theView')->widget("content") ;
if($_aWidget){
	$_aWidget->display($this,null,$aDevice) ;
}else{
	echo '缺少 widget (id:'."content".')' ;
} ?>

		<?php 
$__ui_msgqueue = eval("if(!isset(\$__uivar_theView)){ \$__uivar_theView=&\$aVariables->getRef('theView') ;};
return \$__uivar_theView->widget('content');") ;
if( $__ui_msgqueue instanceof \jc\message\IMessageQueueHolder )
{ $__ui_msgqueue = $__ui_msgqueue->messageQueue() ; }
\jc\lang\Assert::type( '\\jc\\message\\IMessageQueue',$__ui_msgqueue);
if( $__ui_msgqueue->count() ){ 
	$__ui_msgqueue->display($this,$aDevice) ;
} ?>

	</div>
	<?php ob_flush() ;
$theView = $aVariables->get('theView') ;
foreach($theView->iterator() as $aChildView){
	$theView->outputStream()->write($aChildView->outputStream()) ;
}?>
	
	<input type="hidden" id="t" name="t" value="<?php echo eval("if(!isset(\$__uivar_theRequest)){ \$__uivar_theRequest=&\$aVariables->getRef('theRequest') ;};
return \$__uivar_theRequest->get('t');") ;?>" />
	<input type="submit" value="submit" />
	
<input type="hidden" name="<?php echo $aVariables->get('theView')->htmlFormSignature()?>" value="1" /></form><?php } ?>
