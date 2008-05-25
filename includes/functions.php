<?php
function print_pre($var)
{
	echo "<pre>";
	print_r($var);
	echo "</pre>";
}
function deltree($f) {
  if (is_dir($f)) {
    foreach(glob($f.'/*') as $sf) {
      if (is_dir($sf) && !is_link($sf)) {
        deltree($sf);
      } else {
        unlink($sf);
      }  
    }  
  }
  rmdir($f);
}
function property_exists_safe($class, $prop)
{
	$r = property_exists($class, $prop);
	if (!$r) {
		$x = new ReflectionClass($class);
		$r = $x->hasProperty($prop);
	}
	return $r;
}
function &t(&$o)
{return $o;}
?>
