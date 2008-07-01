<!-- ColorPicker -->
<?php echo $p->javascript_before ?>
<script type="text/javascript">
$(document).ready(function(){	$("#<?php echo $p->id?>").attachColorPicker();  });
</script>
<?php echo
"<input type=\"text\" size=\"",$p->size,"\" readonly=\"1\" value=\"",$p->value,"\" name=\"",$p->name,"\"",
$p->javascript, $p->class, $p->style," id=\"",$p->id,"\"", $p->disabled," />",
$p->javascript_after,$p->error_string;
?>
