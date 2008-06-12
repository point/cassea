<?php 
echo $p->javascript_before,"<input type=\"",$p->type,"\" maxlength=\"",$p->maxlength,"\" size=\"",$p->size,
	"\" value=\"",$p->value,"\" name=\"",$p->name,"\" ", $p->javascript, $p->class, $p->style, " id=\"",$p->id,"\" ",
	$p->readonly, $p->disabled, $p->title," />",$p->javascript_after,$p->error_string;?>
