<?php echo
	$p->javascript_before,"<table ",$p->cellpadding, $p->cellspacing, $p->frame, $p->rules, $p->border, $p->javascript,
	$p->class, $p->style,$p->title," id=\"",$p->id,"\" ",$p->width, $p->summary, ">\n",
$p->table_content,
"\n</table>\n",$p->javascript_after;
?>
