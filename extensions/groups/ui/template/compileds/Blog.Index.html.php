<?php
				$__foreach_Arr_var4 = eval("if(!isset(\$__uivar_theModel)){ \$__uivar_theModel=&\$aVariables->getRef('theModel') ;};
return \$__uivar_theModel->childIterator();");
				if(!empty($__foreach_Arr_var4)){ 
					$__foreach_idx_var7 = -1;
					foreach($__foreach_Arr_var4 as $__foreach_key_var6 => &$__foreach_item_var5){
						$__foreach_idx_var7++;
						 $aVariables->set("row",$__foreach_item_var5 ); ?>
	
			<?php echo eval("if(!isset(\$__uivar_row)){ \$__uivar_row=&\$aVariables->getRef('row') ;};
return \$__uivar_row->data('title');") ;?> <a href="?c=blog.update&id=<?php echo eval("if(!isset(\$__uivar_row)){ \$__uivar_row=&\$aVariables->getRef('row') ;};
return \$__uivar_row->data('bid');") ;?>">修改</a> <a href="?c=blog.delete&id=<?php echo eval("if(!isset(\$__uivar_row)){ \$__uivar_row=&\$aVariables->getRef('row') ;};
return \$__uivar_row->data('bid');") ;?>">删除</a> <br />
			
<?php 
					}
				}
			 		?>