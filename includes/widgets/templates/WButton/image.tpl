<!-- button -->
<?php echo $p->javascript_before,"<input ",$p->title, $p->class, $p->style," type=\"image\"  src=\"",$p->src,
"\" name=\"",$p->name,"\" id=\"",$p->id,"\" value=\"",$p->value, "\" alt=\"",$p->alt,"\"",$p->javascript, $p->readonly, $p->disabled," />",
$p->javascript_after;
?>
