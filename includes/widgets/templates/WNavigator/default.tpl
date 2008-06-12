<?php 
echo $p->javascript_before, " <span id=\"",$p->id,"\"", $p->title, $p->class, $p->style, $p->javascript, ">";
foreach($p->steps as $v)
	echo "<a  href=\"",$v['url'],"\" >", $v['title'],"</a> > ";
echo "<span class=\"__nav_last\"><a  href=\"",$p->last_step['url'],"\" >", $p->last_step['title'],"</a></span></span>",$p->javascript_after;
?>
