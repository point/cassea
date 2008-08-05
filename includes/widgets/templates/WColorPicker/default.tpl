<!-- ColorPicker -->
<?php echo $p->javascript_before ?>
<script type="text/javascript">
$(document).ready(function(){
	$("#<?php echo 'div'.$p->id?>").ColorPicker({
		color: '<?php echo $p->value?$p->value:'#ffffff'?>',
		onShow: function (colpkr) {
			$(colpkr).fadeIn(500);
			return false;
		},
		onHide: function (colpkr) {
			$(colpkr).fadeOut(500);
			return false;
		},
		onChange: function (hsb, hex, rgb) {
			$('#<?php echo 'div'.$p->id?> div').css('backgroundColor', '#' + hex);
			$('#<?php echo $p->id?>').val('#'+hex);
		}
	});
});
</script>
<div id="<?php echo 'div'.$p->id?>" class="CS"><div style="background-color: <?php echo $p->value?$p->value:'#ffffff'?>">
</div></div>
<input type="text" id="<?php echo $p->id?>" name="<?php echo $p->name?>" style="display:none" value="<?php $p->value?>"/>
<?php echo $p->javascript_after,$p->error_string;?>
