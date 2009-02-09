<?php 
echo $p->javascript_before,"<input type=\"file\" size=\"",$p->size,
	"\" value=\"",$p->value,"\" name=\"",$p->name,"\" ", $p->javascript, $p->class, $p->style, " id=\"",$p->id,"\" ",
	$p->readonly, $p->disabled, $p->title," />",$p->javascript_after,$p->error_string;?>
    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $p->max_file_size;?>" />