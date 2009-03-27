<!-- calendar -->
<?php echo $p->javascript_before, "<input ",$p->title," type=\"text\" size=\"",$p->size,"\" readonly=\"1\" value=\"",
$p->value,"\" name=\"",$p->name, "\"", $p->javascript, $p->class, $p->style, " id=\"",$p->id,"\"",$p->disabled, " />",
$p->javascript_after,$p->error_string,
"<script type=\"text/javascript\">$(\"#",$p->id,"\").datepicker({ dateFormat: '",$p->date_format,"',showOn: 'both', buttonImageOnly: true, buttonImage:'/w_images/calendar.gif',yearRange: '-80:+30',
changeMonth:true, changeYear:true, constrainInput: true, showButtonPanel: true});</script>";
?>
