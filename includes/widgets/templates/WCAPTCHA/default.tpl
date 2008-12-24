<script type="text/javascript">
$(document).ready(function(){
	$("#<?php echo $p->id?>_reload").click(function()	{
		var el = $("#<?php echo $p->id?>_image");
		el.attr('src',el.attr('src').replace(/\d+\/$/,Math.floor(Math.random()*1000)+"/"));
	}); 
});
</script>
<?php echo $p->javascript_before,
"<div class=\"captcha\"><label for=\"",$p->id,"\"><img  ",$p->alt, $p->width, $p->height, " src=\"",$p->src,"\"", $p->javascript,$p->class, $p->style, $p->title, " id=\"",
$p->id,"_image\" /></label><br/>
<a id=\"",$p->id,"_reload\" class=\"captcha_reload\">",$p->text,"</a><br/><input type=\"text\" id=\"",$p->id,"\" name=\"",$p->id,"\" autocomplete=\"off\"></div>",
"<div class=\"filter_error\">",$p->error_string,"</div>",$p->javascript_after ?>
