<?php if((string)$p->code): 
	if($p->condition)
		echo "<!--[if ",$p->condition,"]>\n";
?>
<script type="text/javascript">
<?php echo $p->code; ?>
</script>
<?php 
	if($p->condition)
		echo "<![endif]-->";
endif; ?>
