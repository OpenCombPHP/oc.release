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
	
	<div id="items">
		<?php
				$__foreach_Arr_var0 = eval("if(!isset(\$__uivar_theModel)){ \$__uivar_theModel=&\$aVariables->getRef('theModel') ;};
return \$__uivar_theModel->child('poll')->child('item')->childIterator();");
				if(!empty($__foreach_Arr_var0)){ 
					$__foreach_idx_var3 = -1;
					foreach($__foreach_Arr_var0 as $__foreach_key_var2 => &$__foreach_item_var1){
						$__foreach_idx_var3++;
						 $aVariables->set("row",$__foreach_item_var1 );  $aVariables->set("i",$__foreach_idx_var3 ); ?>
		<div>
			<span class='itemContent'>投票内容<?php echo eval("if(!isset(\$__uivar_i)){ \$__uivar_i=&\$aVariables->getRef('i') ;};
return \$__uivar_i+1;") ;?></span>：<a href='javascript:;' onClick='addItem()'>增加</a><?php $_aWidget = $aVariables->get('theView')->widget(eval("if(!isset(\$__uivar_i)){ \$__uivar_i=&\$aVariables->getRef('i') ;};
return 'poll_item_title_'.\$__uivar_i;")) ;
if($_aWidget){
	$_aWidget->display($this,null,$aDevice) ;
}else{
	echo '缺少 widget (id:'.eval("if(!isset(\$__uivar_i)){ \$__uivar_i=&\$aVariables->getRef('i') ;};
return 'poll_item_title_'.\$__uivar_i;").')' ;
} ?>

			<?php 
$__ui_msgqueue = eval("if(!isset(\$__uivar_theView)){ \$__uivar_theView=&\$aVariables->getRef('theView') ;};
if(!isset(\$__uivar_i)){ \$__uivar_i=&\$aVariables->getRef('i') ;};
return \$__uivar_theView->widget('poll_item_title_'.\$__uivar_i);") ;
if( $__ui_msgqueue instanceof \jc\message\IMessageQueueHolder )
{ $__ui_msgqueue = $__ui_msgqueue->messageQueue() ; }
\jc\lang\Assert::type( '\\jc\\message\\IMessageQueue',$__ui_msgqueue);
if( $__ui_msgqueue->count() ){ 
	$__ui_msgqueue->display($this,$aDevice) ;
} ?>

		</div>
		<?php 
					}
				}
			 		?>
	</div>
	<input type="hidden" id="itemSum" name="itemSum" value="<?php echo eval("if(!isset(\$__uivar_theModel)){ \$__uivar_theModel=&\$aVariables->getRef('theModel') ;};
return \$__uivar_theModel->child('poll')->child('item')->childrenCount();") ;?>" />
	<script>
	function addItem(){
		
		var newItem = jQuery("#items > div").eq(0).clone(true);
		var nowNum = parseInt(jQuery("#itemSum").val()) + 1 ;
		newItem.find(".itemContent").html("投票内容" + nowNum);
		newItem.find("input").attr("id","poll_item_title_"+(nowNum-1));
		newItem.find("input").attr("name","poll_item_title_"+(nowNum-1));
		newItem.find("input").val("");
		jQuery("#items").append(newItem);
		jQuery("#itemSum").val(nowNum);
	}
	</script>