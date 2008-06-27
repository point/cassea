<div id="flashcontent_<?php echo $p->id?>">
	<strong>You need to upgrade your Flash Player</strong>
</div>
<script type="text/javascript">
	// <![CDATA[
	swfobject.embedSWF("<?php echo $p->src?>", "flashcontent_<?php echo $p->id?>", "<?php echo $p->width?>", "<?php echo $p->height?>", "7.0.0");
	// ]]>
</script>
