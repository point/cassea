<?php echo $p->javascript_before;
	if($p->condition)
		echo "<!--[if ",$p->condition,"]>\n";
	echo "<style type=\"text/css\"", $p->media?"media=\"{$p->media}\"":"", $p->title,">\n",$p->content,"\n</style>";
	if($p->condition)
		echo "\n<![endif]-->";
echo $p->javascript_after;
?>
