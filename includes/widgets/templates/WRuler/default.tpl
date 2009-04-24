<ul class="ruler">
<?php 
if((string)$p->show_prev)
{
?>
<li class="first"><a href="<?php echo $p->first_link?>">&laquo;</a></li>
<?php 
}

foreach($p->items as $v)
{?><li <?php echo $v['class']?>><a href="<?php echo $v['link']?>"><?php echo $v['title']?></a></li>
<?php 
}
if((string)$p->show_next)
{
?>
<li class="last"><a href="<?php echo $p->last_link?>">&raquo;</a></li>
<?php 
}
?>
</ul>
