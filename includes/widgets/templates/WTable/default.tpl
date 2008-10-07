<?php 
if((string)$p->even_class || (string)$p->odd_class || (string)$p->hover_class) 
{?>
<script type="text/javascript">
$(document).ready(function(){
<?php if((string)$p->even_class)
{	?>	$("#<?php echo $p->id;?> > tbody > tr:even").addClass('<?php echo $p->even_class;?>');
<?php
}
if((string)$p->odd_class)
{	?>	$("#<?php echo $p->id;?> > tbody > tr:odd ").addClass('<?php echo $p->odd_class;?>');
<?php 
}
if((string)$p->hover_class)
{	?>	$("#<?php echo $p->id;?> > tbody > tr ").hover(function(){ $(this).addClass('<?php echo $p->hover_class;?>')},function(){$(this).removeClass('<?php echo $p->hover_class;?>')});
<?php
} ?>
});
</script>
<?php
}
if((string)$p->table_sorter)
{?>
<script type="text/javascript">
$(document).ready(function() 
{ $("#<?php echo $p->id?>").tablesorter();  } 
); 
</script>
<?php 
}
?>
<?php echo
	$p->javascript_before,"<table ",$p->cellpadding, $p->cellspacing, $p->frame, $p->rules, $p->border, $p->javascript,
	$p->class, $p->style,$p->title," id=\"",$p->id,"\" ",$p->width, $p->summary, ">\n",
$p->table_content,
"\n</table>\n",$p->javascript_after;
?>
