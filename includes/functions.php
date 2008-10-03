<?php
/*- vim:expandtab:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008, Cassea Project
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*     * Redistributions of source code must retain the above copyright
*       notice, this list of conditions and the following disclaimer.
*     * Redistributions in binary form must reproduce the above copyright
*       notice, this list of conditions and the following disclaimer in the
*       documentation and/or other materials provided with the distribution.
*     * Neither the name of the Cassea Project nor the
*       names of its contributors may be used to endorse or promote products
*       derived from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY CASSEA PROJECT ''AS IS'' AND ANY
* EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL CASSEA PROJECT BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
}}} -*/

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
function requestURI($full = 0)
{	
	$s = $full?"http://".$_SERVER['SERVER_NAME']:"";
	$uri = $_SERVER['REQUEST_URI'];
	if(strpos($uri,"javascript:") !== false)
		$uri = str_replace("javascript:","",$uri);
	return $s.Filter::filter($uri,Filter::STRING_QUOTE_ENCODE);
}
function getImgSizeNoCache($path = null)
{
	if(!isset($path)) return false;
	$full_path = Config::get("IMAGES_PATH")."/".$path;
	if(!file_exists($full_path)) return false;

	$ret = array();
	try{
		$i = new Imagick($full_path);
		$ret[0] = $ret['width'] = $i->getImageWidth();
		$ret[1] = $ret['height'] = $i->getImageHeight();
	}catch(Exception $e){ return false;}
	return $ret;
}
function getImgSizeCache($path = null)
{

	$v = Storage::create("images_size");
	if(($ret = $v->get($path)) !== false)
		return $ret;
	if(($ret = getImgSizeNoCache($path)) !== false)
	{
		$v->set($path,$ret);
		return $ret;
	}
	return false;
}
function getImgSize($path = null)
{
	if(Config::get("USE_IMAGES_CACHE"))
		return getImgSizeCache($path);
	else
		return getImgSizeNoCache($path);
}
function recalcSize($width,$height,$max_width = null,$max_height = null)
{
	if((int)$max_width < 1)
		$max_width = $width;
	if((int)$max_height < 1)
		$max_height = $height;

	$m = min($max_width / $width,$max_height / $height);
	if($m > 1) return array($width,$height);
	$a = array(round($width*$m),round($height*$m));
	$a['width'] = $a[0];$a['height'] = $a[1];
	return $a;

}
function recalcSizeArray($arr,$max_width = null,$max_height = null)
{
	if($arr === false) return array(1,1);
	return recalcSize($arr['width']?$arr['width']:$arr[0],$arr['height']?$arr['height']:$arr[0],$max_width,$max_height);
}

function pageChanged($path,$time)
{
	if(!file_exists($path))
	{
		$path = Config::get('ROOT_DIR').$path;
		if(!file_exists($path)) return true;
	}

	$stat = stat($path);
	if($stat['mtime'] > $time) return true;
	else return false;
}
?>
