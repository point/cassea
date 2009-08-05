<?php
 echo $p->javascript_before," <input type=\"radio\" ",$p->checked," name=\"",$p->name,"\" ",$p->javascript, $p->class, $p->tabindex,
 $p->style, " value=\"",$p->value,"\" id=\"",$p->id,"\" ",$p->readonly, $p->disabled, $p->title," />",$p->label,$p->javascript_after,$p->error_string;
 ?>
