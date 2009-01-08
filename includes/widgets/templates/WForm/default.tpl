<?php 
if(isset($p->vc_rules) && isset($p->vc_messages)) 
{?>
<script type="text/javascript">

$(document).ready(function() {
	// validate signup form on submit
	var validator_<?php echo $p->id?> = $("#<?php echo $p->id?>").validate({
	rules:{
	<?php echo $p->vc_rules?>
	},
<?php $messages = (string)$p->vc_messages;if(!empty($messages))
{?>
	messages:{
	<?php echo $p->vc_messages;?>
	},
<?php
} ?>
	onsubmit:true,
	onkeyup:false,
	focusInvalid:true
});
	$("#<?php echo $p->id?>").submit(function(){
		$(".widget_error").remove();
		});
});
</script>
<?php }?>
<?php echo
$p->javascript_before,"<form ",$p->javascript, $p->class, $p->style, $p->title, " id=\"",$p->id,"\" name=\"",
$p->id,"\" action=\"",$p->action,"\" enctype=\"",$p->enctype,"\" method=\"",$p->method,"\">",
	$p->form_content,
(isset($p->signature))?"<input type=\"hidden\" name=\"{$p->signature_name}\" value=\"{$p->signature}\"/>":"",
(isset($p->formid_name))?"<input type=\"hidden\" name=\"{$p->formid_name}\" value=\"{$p->id}\"/>":"",
"</form>",$p->javascript_after;
?>
