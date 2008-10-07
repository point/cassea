<?php echo $p->javascript_before,"<input type=\"hidden\" name=\"",$p->name,"\" ", $p->javascript, " id=\"",
	$p->id,"\" value=\"",$p->value,"\"/>",$p->javascript_after,$p->error_string; ?>
