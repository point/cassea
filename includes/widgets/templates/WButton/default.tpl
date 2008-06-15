<!-- button -->
<?php echo $p->javascript_before," <input ",$p->title, $p->class,$p->style," type=\"",$p->type,"\"  name=\"",$p->name,
" id=\"",$p->id,"\" value=\"",$p->value,"\" ",  $p->javascript, $p->readonly, $p->disabled, " />",$p->javascript_after;
?>
