<?php echo
$p->javascript_before,"<fieldset ", $p->javascript, $p->class, $p->style, $p->title," id=\"",$p->id,"\">
<legend>",$p->legend,"</legend>\n",
	$p->fieldset_content,
"\n</fieldset>",$p->javascript_after;?>
