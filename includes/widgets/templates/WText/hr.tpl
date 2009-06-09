<?php echo $p->javascript_before;
for($i = 0; $i < (string)$p->repeat_count; $i++)
	echo "<hr ",$p->javascript, $p->class, $p->style, $p->title, "id=\"",$p->id,$i,"\"/>";
echo $p->javascript_after ?>
