<?php echo 
	$p->javascript_before;
	if((string)$p->thead)
		echo "<thead>";
	echo "<tr ",$p->align, $p->valign, $p->title," id=\"",$p->id,"\" ",$p->javascript, $p->class, 
	$p->style,">",
$p->row_content,
"</tr>";
if((string)$p->thead)
	echo "</thead>";
echo $p->javascript_after;?>
