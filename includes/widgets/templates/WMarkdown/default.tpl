<script language="javascript" type="text/javascript">
$(document).ready(function(){
    <?php if (isset($p->preview_template)){ ?>
    mySettings.previewParserPath = '/markdownparser/index.html?preview_template=<?php echo $p->preview_template;?>',
    <?php } ?>
    $('#<?php echo $p->id?>').markItUp(mySettings);
	});
</script>
<?php echo 
$p->javascript_before, "<textarea ",$p->javascript, $p->class,  $p->style," id=\"",$p->id,"\" cols=\"",
$p->cols,"\" rows=\"",$p->rows,"\" name=\"",$p->name,"\"", $p->title, $p->readonly, $p->disabled,">",$p->value,
"</textarea>",$p->javascript_after;
?>
