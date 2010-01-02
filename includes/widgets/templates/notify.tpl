<div id='notifyBlock' class='center' />
<script type="text/javascript"> 
$(document).ready(function(){ 
$.jGrowl.defaults.closer = false;
$.jGrowl.defaults.life = 4000;
$('#notifyBlock')
<?php foreach($p->list as $text): ?>
	.jGrowl("<?php echo $text; ?>")
<?php endforeach; ?>
});
</script>
