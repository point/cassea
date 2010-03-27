<script type="text/javascript">
$(document).ready(function(){
$("#<?php echo $p->id?> + img.showpass").click(function(){
var el = $(this).prev().get(0);
el.type= el.type=="password"?"text":"password"; });
});
</script>
<?php 
echo $p->javascript_before,"<input type=\"",$p->type,"\" maxlength=\"",$p->maxlength,"\" size=\"",$p->size,
	"\" value=\"",$p->value,"\" name=\"",$p->name,"\" ", $p->javascript, $p->class, $p->style, " id=\"",$p->id,"\" ",
	$p->readonly, $p->disabled, $p->title, $p->tabindex," />",$p->javascript_after,$p->error_string ;?>
    <img class="showpass" align="middle" width="16" height="16" id="switch" title="<?php echo $p->key_title?>" alt=" *****" src="/w_images/switch.png"/>
