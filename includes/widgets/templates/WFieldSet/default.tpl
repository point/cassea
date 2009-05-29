<?php echo
$p->javascript_before,"<fieldset ", $p->javascript, $p->class, $p->style, $p->title," id=\"",$p->id,"\">";
if($p->legend) echo "<legend>",$p->legend,"</legend>\n";
echo $p->fieldset_content,
"\n</fieldset>",$p->javascript_after;?>
