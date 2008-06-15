<?php
echo $p->javascript_before,"<input type=\"checkbox\" ",$p->checked," name=\"",$p->name,"\" ", $p->javascript, $p->title,
$p->class, $p->style," id=\"",$p->id,"\" ",$p->readonly, $p->disabled, " value=\"",$p->value,"\"/>&nbsp;<label for=\"",
$p->id,"\">",$p->text,"</label>",$p->javascript_after,$p->error_string;
?>
