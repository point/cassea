<script type="text/javascript">
$(document).ready(function(){
    <?php $tpl=isset($p->preview_template)?('?preview_template='.$p->preview_template):''; ?>
    mySettings.previewParserPath = '/markdown/parse.html<?php echo $tpl; ?>',
    $('#<?php echo $p->id?>').markItUp(mySettings);
	});
</script>
<?php echo 
$p->javascript_before, "<textarea ",$p->javascript, $p->class,  $p->style," id=\"",$p->id,"\" cols=\"",
$p->cols,"\" rows=\"",$p->rows,"\" name=\"",$p->name,"\"", $p->title, $p->readonly, $p->disabled,">",$p->value,
"</textarea>",$p->javascript_after;
?>
