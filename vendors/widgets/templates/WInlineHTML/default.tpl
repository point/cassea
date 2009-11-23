<?php 
if((string)$p->use_short_tag)
	echo "<",$p->real_tagname," ",$p->attributes," />";
else echo 
	"<",$p->real_tagname," ",$p->attributes,">\n",
$p->content,"\n</",$p->real_tagname,">";
?>
