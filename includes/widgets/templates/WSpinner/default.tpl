<!-- Spinner -->
<script type="text/javascript">
$(document).ready(function(){
	$("#<?php echo $p->id?>").SpinButton({
		min:<?php echo $p->min?>,
		max:<?php echo $p->max?>,
		step:<?php echo $p->step?>,
		spinClass:"spinner",
		upClass:"spinner_up",
		downClass:"spinner_down"
	});
});
</script>
<?php echo
$p->javascript_before,"<input type=\"text\" size=\"".$p->size,"\" ",$p->readonly, " value=\"",$p->value,"\" name=\"",
$p->name,"\" ",$p->javascript, $p->class, $p->style, "id=\"",$p->id,"\" ",$p->disabled, $p->title, "/>",
"<span class=\"spinner_text\">",$p->text,"</span>",$p->javascript_after,$p->error_string;?>
