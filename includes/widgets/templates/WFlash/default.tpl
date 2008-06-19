<div id="flashcontent_<?php echo $p->id?>">
	<strong>You need to upgrade your Flash Player</strong>
</div>
<script type="text/javascript">
	// <![CDATA[
	var so_<?php echo $p->id?> = new SWFObject("<?php echo $p->src?>", "flash", "<?php echo $p->width?>", "<?php echo $p->height?>", "7", "<?php echo $p->ngcolor?>");
	so.write("flashcontent_<?php echo $p->id?>");
	// ]]>
</script>
