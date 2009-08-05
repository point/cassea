<?php echo $p->javascript_before, "<select ", $p->javascript, $p->class, $p->style, $p->title, $p->size, $p->multiple, $p->disabled, $p->tabindex, 
" name=\"",$p->name,"\"",
" id=\"",$p->id,"\"", $p->title,">\n",
	$p->select_content,
"\n</select>",$p->javascript_after,$p->error_string;?>
