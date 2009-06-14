<?php
/*- vim:noet:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008,2009 Cassea Project
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
	$s = $full?"http://".$_SERVER['HTTP_HOST']:"";
	$uri = $_SERVER['REQUEST_URI'];
	if(strpos($uri,"javascript:") !== false)
		$uri = str_replace("javascript:","",$uri);
	return $s.Filter::filter(Filter::sanitizeVars($uri),Filter::STRING_QUOTE_ENCODE);
}
function getImgSizeNoCache($path = null)
{
	if(!isset($path)) return false;
	$full_path = Config::get("root_dir").Config::get("IMAGES_DIR")."/".$path;
	if(!file_exists($full_path)) return false;

	$ret = array();
	try{
        if(class_exists("Imagick"))
        {
		    $i = new Imagick($full_path);
		    $ret[0] = $ret['width'] = $i->getImageWidth();
		    $ret[1] = $ret['height'] = $i->getImageHeight();
        }
        elseif(function_exists('getimagesize'))
        {
            list($width, $height) = getimagesize($full_path);
            $ret[0] = $ret['width'] = $width;
            $ret[1] = $ret['height'] = $height;
        }
        else return false;
	}catch(Exception $e){ return false;}
	return $ret;
}
function getImgSizeCache($path = null)
{
	$v = Storage::create("images_size");
	if(($ret = $v->get($path)) !== false && ($stat =stat(Config::get("root_dir").Config::get("IMAGES_DIR")."/".$path)) !== false && $stat['mtime'] <= $ret['mtime'])
        return $ret;
	if(($ret = getImgSizeNoCache($path)) !== false)
    {
        if(($stat = stat(Config::get("root_dir").Config::get("IMAGES_DIR")."/".$path)) !== false)
            $ret['mtime'] = $stat['mtime'];
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
    if((int)$width < 1 || (int)$height < 1)
        return array(0,0,"width"=>0,"height"=>0);
	if((int)$max_width < 1)
		$max_width = $width;
	if((int)$max_height < 1)
		$max_height = $height;

	$m = min($max_width / $width,$max_height / $height);
	if($m > 1) return array($width,$height,"width"=>$width,"height"=>$height);
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

function randStr($length)
{
    $str='123456789WERTYUIPASDFGHJKLZXCVBNM';
    $res='';
    for($i=0;$i<$length;$i++)
        $res.=$str[mt_rand(0,strlen($str)-1)];
    return $res;
}

function generateCAPTCHA(&$str)
{
    $image=new IMagick();
    $draw=new ImagickDraw();
    $image->newImage(155,50,new ImagickPixel(Config::getInstance()->captcha->background));
    $draw->setFontSize(42);
    $draw->setFont(Config::get('root_dir').'/tools/c.ttf');
    $pixel=new ImagickPixel(Config::getInstance()->captcha->font_color);
    $draw->setFillColor($pixel);
        for($j=1;$j<4;$j++)
        {
            for($i=0;$i<100;$i++)
            {
                $a[$i]['x']=$i*357;
                $a[$i]['y']=round(2*sin($i+mt_rand(1,7))+$j*12);
            }
            $draw->polyline($a);
            $image->drawImage($draw);
         }   
     $str=randStr(Config::getInstance()->captcha->word_length); 

     $image->annotateImage($draw,5,40,0,$str);
     $image->waveImage(mt_rand(3,5),mt_rand(30,60));
     $image->vignetteImage(5,150,0,0);
     $image->swirlImage(mt_rand(10,39));
     $image->setImageFormat('png');
     return $image;
}
function CAPTCHACheckAnswer($str)
{
    $s=Storage::createWithSession("_CAPTCHA_",60);    
    if((strtoupper($str) === $s->get("answer"))&&(Controller::getInstance()->getPage() === $s->get("page"))) 
        return true;
    return false;
}
function getMime($file)
{
    if(!file_exists($file)) return null;
    if(!extension_loaded('fileinfo') && !@dl('fileinfo')) return null;
    
    $finfo = finfo_open(FILEINFO_MIME);
    if(!$finfo) return null;
    $mime = finfo_file($finfo,$file);
    finfo_close($finfo);
    return $mime;
}
function sizeFromString($size)
{
	if (is_null($size)) return 0;
    if (is_numeric($size)) return (integer) $size;

	if (!preg_match('/^(\d+)(\s|&nbsp;)*([kmgtpezy])?(b)?$/i', trim($size), $m)) throw new Exception('Incorrect string format "'.$size.'"');
	$value = $m[1];
	if(isset($m[3])) $mul = strtoupper($m[3]);
	else return $value;

	switch ($mul)     
	{
        case 'Y': $value *= 1208925819614629174706176;          break;
        case 'Z': $value *= 1180591620717411303424;				break;
        case 'E': $value *= 1152921504606846976;	            break;
        case 'P': $value *= 1125899906842624;		            break;
        case 'T': $value *= 1099511627776;			            break;
        case 'G': $value *= 1073741824;				            break;
        case 'M': $value *= 1048576;							break;
        case 'K': $value *= 1024;								break;
    }
    return $value;
}

function sizeToString($size,$flag=0)
{
    $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    for ($i=0; $size >= 1024 && $i < 9; $i++) 
        $size /= 1024;
    if($flag)
        return round($size, 2) . ' ' .$sizes[$i];
    else
        return round($size, 2) . $sizes[$i];
}

function moveUp($table,$priority_field,$id_field,$id,$priority=null)
{
    if($priority===null)
    {
        $r = DB::query('SELECT '.$priority_field.' as priority FROM '.$table.' WHERE '.$id_field.'="'.$id.'"');
        $rating=$r[0]['priority'];
    }
    else
        $rating=$priority;
    $maxq= DB::query('SELECT '.$id_field.' as id, '.$priority_field.' as priority FROM '.$table.' WHERE '.$priority_field.'=(SELECT max('.$priority_field.') FROM '.$table.' WHERE '.$priority_field.'<'.$rating.')');
    if(!$maxq) return;
    $maxid=$maxq[0]['id'];
    $max=$maxq[0]['priority'];
    return $up = DB::multiQuery('UPDATE '.$table.' SET '.$priority_field.'="'.$max.'" WHERE '.$id_field.'="'.$id.'";UPDATE '.$table.' SET '.$priority_field.'="'.$rating.'" WHERE '.$id_field.'="'.$maxid.'"');
}
function moveDown($table,$priority_field,$id_field,$id,$priority=null)
{

    if($priority===null)
    {
        $r = DB::query('SELECT '.$priority_field.' as priority FROM '.$table.' WHERE '.$id_field.'="'.$id.'"');
        $rating=$r[0]['priority'];
    }
    else 
        $rating=$priority;
    $minq = DB::query('SELECT '.$id_field.' as id, '.$priority_field.'  as priority FROM '.$table.' WHERE '.$priority_field.'=(SELECT min('.$priority_field.') FROM '.$table.' WHERE '.$priority_field.'>'.$rating.')');
    if(!$minq) return;
    $minid=$minq[0]['id'];
    $min=$minq[0]['priority'];
    return $down = DB::multiQuery('UPDATE '.$table.' SET '.$priority_field.'="'.$min.'" WHERE '.$id_field.'="'.$id.'";UPDATE '.$table.' SET '.$priority_field.'="'.$rating.'" WHERE '.$id_field.'="'.$minid.'"');
}

function nameToClass($name){
	return strtoupper(substr($name,0,1)).substr($name,1);
}

?>
