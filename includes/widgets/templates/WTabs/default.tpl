<?php if(count($p->href)) { ?>
<script type="text/javascript">
$(document).ready(function(){
		var tabs = $("#<?php echo $p->id?> ").tabs({selected:<?php echo $p->selected?>,cache:true});
		});
</script>
<?php } ?>

<?php echo 
$p->javascript_before,"<div ",$p->title,  $p->javascript, $p->class, $p->style, $p->title, " id=\"",$p->id,"\">
<ul>";
foreach($p->href as $k=>$v)
{
	echo "<li><a href=\"",$v,"\"><span>",$p->title[$k],"</span></a></li>\n";
}
echo "\n</ul>\n",$p->tabs,"</div>",$p->javascript_after;
?>
