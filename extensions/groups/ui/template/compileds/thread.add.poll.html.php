	<div>
		最多选择：<?php $_aWidget = $aVariables->get('theView')->widget("poll_maxitem") ;
if($_aWidget){
	$_aWidget->display($this,null,$aDevice) ;
}else{
	echo '缺少 widget (id:'."poll_maxitem".')' ;
} ?>

		<?php 
$__ui_msgqueue = eval("if(!isset(\$__uivar_theView)){ \$__uivar_theView=&\$aVariables->getRef('theView') ;};
return \$__uivar_theView->widget('poll_maxitem');") ;
if( $__ui_msgqueue instanceof \jc\message\IMessageQueueHolder )
{ $__ui_msgqueue = $__ui_msgqueue->messageQueue() ; }
\jc\lang\Assert::type( '\\jc\\message\\IMessageQueue',$__ui_msgqueue);
if( $__ui_msgqueue->count() ){ 
	$__ui_msgqueue->display($this,$aDevice) ;
} ?>

	</div>
	
	<div>
		投票内容：<?php $_aWidget = $aVariables->get('theView')->widget("poll_item_title") ;
if($_aWidget){
	$_aWidget->display($this,null,$aDevice) ;
}else{
	echo '缺少 widget (id:'."poll_item_title".')' ;
} ?>

		<?php 
$__ui_msgqueue = eval("if(!isset(\$__uivar_theView)){ \$__uivar_theView=&\$aVariables->getRef('theView') ;};
return \$__uivar_theView->widget('poll_item_title');") ;
if( $__ui_msgqueue instanceof \jc\message\IMessageQueueHolder )
{ $__ui_msgqueue = $__ui_msgqueue->messageQueue() ; }
\jc\lang\Assert::type( '\\jc\\message\\IMessageQueue',$__ui_msgqueue);
if( $__ui_msgqueue->count() ){ 
	$__ui_msgqueue->display($this,$aDevice) ;
} ?>

	</div>
	
