<?php echo 
$p->javascript_before, "<textarea ",$p->javascript, $p->class,  $p->style," id=\"",$p->id,"\" cols=\"",
$p->cols,"\" rows=\"",$p->rows,"\" name=\"",$p->name,"\"", $p->title, $p->readonly, $p->disabled,">",$p->value,
"</textarea>",$p->javascript_after;
?>
