<?php echo 
	$p->javascript_before,"<tr ",$p->align, $p->valign, $p->title," id=\"",$p->id,"\" ",$p->javascript, $p->class, 
	$p->style,">",
$p->row_content,
"</tr>",$p->javascript_after;?>
