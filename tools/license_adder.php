<?php
function lic($f) {
	global $lic;
	global $exclude;
	if (is_dir($f))
		foreach(glob($f.'/*') as $sf) 
			if (is_dir($sf) && !is_link($sf) && !in_array(basename($sf),$exclude))
				lic($sf);
			elseif(preg_match("/([^\/]+)\.php$/",$sf,$m) && $m[1] != 'license_adder' && $f = file_get_contents($sf))
				if(!preg_match('/<\?php\n+\/\*-.+-\*\//ms',$f))
					file_put_contents($sf,preg_replace('/<\?php\n*/','<?php'."\n".$lic,$f,1));
				else 
					file_put_contents($sf,preg_replace('/<\?php\n*\/\*-.+\*\/\n*/ms','<?php'."\n".$lic,$f));
}
$lic = file_get_contents('license');
$exclude = array("web");
if(empty($lic)) return;
lic("..");
?>
