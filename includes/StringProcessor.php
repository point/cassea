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

interface StringProcessable{};

class StringProcessorFactory
{
    static function create($str)
    {
        $o = new StringProcessor;
        if(empty($str)) return $o;

        $processors = explode("|",$str);
        $m = array();
        foreach($processors as $proc)
        {
            $proc = trim($proc);;
            $p1 = explode("'",$proc);
            $p2 = array();
            for($i = 0, $c = count($p1); $i < $c; $i++)
                if($i%2)
                    $p2[] = trim($p1[$i]);
                else
                    $p2 = array_merge($p2,explode(" ",trim($p1[$i])));
            foreach($p2 as &$v) if($v === "null") $v = null;
            $o->addProcessor($p2[0],array_slice($p2,1));
        }
        return $o;
    }
}
// {{{ StringProcessor 
class StringProcessor
{
    protected
        $processors = array()
        ;
    function addProcessor($name,$parameters = array())
    {
        if(!is_string($name) || empty($name)) return;
        $this->processors[] = array("name"=>$name,"parameters"=>$parameters);
    }
    function process($string)
    {
        if(empty($this->processors) || $string === "") return $string;
        $s = $string;
        foreach($this->processors as $p)
        {
            $params = $p['parameters'];
            array_unshift($params,$s);
            if(method_exists($this,$p['name']))
                $s = call_user_func_array(array($this,$p['name']),$params);
            /*elseif(is_callable($p['name']))
                $s = call_user_func_array($p['name'],$params);*/
        }
        return $s;
    }

    protected function limitwords($string, $limit = 50, $ends = '...') 
    {
        if (strlen($string) <=  $limit) return $string;

        $pos = array_keys(str_word_count($string, 2));

        $min_d = $limit - $pos[0];
        $min_p = 0;
        for($i = 1, $c = count($pos);$i < $c,$pos[$i] <= $limit;$i++)
            if($limit - $pos[$i] < $min_d)
                $min_d = $limit - $pos[$i] and $min_p = $i;

        return (substr($text, 0, $pos[$min_p]).$ends);
    }

    protected function filesize($size, $flag=0){
        return sizeToString($size,$flag);
    }

    protected function nl2pbr($value) 
    {
        $result = array();
        foreach (preg_split('/(\r?\n){2,}/m', $value) as $part) 
            $result[] = '<p>' . nl2br($part) . '</p>';
        
        return implode("\n", $result);
    }

    protected function date($time, $format = 'jS F Y H:i') 
    {
        return date($format, is_numeric($time)?$time:strtotime($time));
    }
    protected function date_locale($time,$locale = "ru_RU.UTF-8")
    {
        setlocale(LC_TIME,$locale);
        return strftime("%e %B %Y, %R:%M", is_numeric($time)?$time:strtotime($time));
    }

    // принимает число (например, 11), 
    // базовую часть языковой константы (например, hour)
    // врзвращает час (часа, часов).
    protected final function _plural($number, $lang_const_base)
    {
        return Language::message('widgets',Language::getPluralConst($number,$lang_const_base));
    }
    protected function relative_time($time, $locale = "ru_RU.UTF8") 
    {
        setlocale(LC_TIME,$locale);
        $timestamp = is_numeric($time)?$time:strtotime($time);
        $time   = mktime(0, 0, 0);
        $delta  = time() - $timestamp;
        $string = '';
        
        if ($timestamp < $time - 86400) 
            //return date("F j, Y, g:i a", $timestamp);
            return strftime("%B %e, %Y, %R:%M", $timestamp);

        if ($delta > 86400 && $timestamp < $time) 
            return Language::message('widgets',"Yesterday_at")." " .strftime("%R:%M", $timestamp);

        if ($delta > 7200)
            $string .= ($f = floor($delta / 3600))." ".$this->_plural($f,"hour").", ";
        else if ($delta > 3660)
            $string .= "1 ".$this->_plural(1, "hour").", ";
        else if ($delta >= 3600)
            $string .= "1 ".$this->_plural(1, "hour")." ";
        $delta  %= 3600;
        
        if ($delta > 60)
            $string .= ($f = floor($delta / 60)) . " ".$this->_plural($f,"minutes")." ";
        else
            $string .= abs($delta)." ".$this->_plural(abs($delta),"seconds")." ";
        return ($delta > 0)?($string." ".Language::message('widgets',"ago")):(Language::message('widgets',"date_in")." ".$string);
    }

    protected function relative_date($time,$locale = "ru_RU.UTF8") 
    {
        $time = is_numeric($time)?$time:strtotime($time);
        $today = strtotime(date('M j, Y'));
        $reldays = ($time - $today)/86400;
        if ($reldays >= 0 && $reldays < 1) 
            return Language::message('widgets',"today");
         else if ($reldays >= 1 && $reldays < 2) 
            return Language::message('widgets',"tomorrow");
         else if ($reldays >= -1 && $reldays < 0) 
            return Language::message('widgets',"yesterday");
        
        
        if (abs($reldays) < 7) 
            if ($reldays > 0) 
                return Language::message('widgets','date_in').' '.($reldays = floor($reldays)).$this->_plural($reldays,' day') ;
             else 
                return ($reldays = abs(floor($reldays)))." ".$this->_plural($reldays,"day")." ".Language::message('widgets',"ago");
            
        
        setlocale(LC_TIME,$locale);

        if (abs($reldays) < 182) 
            $ret = strftime("%A, %B %e",$time ? $time : time());
         else 
             $ret = strftime("%A, %B %e, %Y",$time ? $time : time());

        return $ret;
    }
    
    protected function relative_datetime($time, $locale = "ru_RU.UTF8") 
    {
        $date = self::relative_date($time,$locale);
        if ($date == Language::message('widgets',"today")) 
            return self::relative_time($time,$locale);
        return $date;
    }
    protected function md5($string)
    {return md5($string);}
    protected function sha1($string)
    {return sha1($string);}
    protected function wordwrap($string, $width = 75, $break = "\n", $cut = false)
    {return wordwrap($string,$width,$break,$cut);}
    protected function trim($string)
    {return trim($string);}
    protected function upper($string)
    {return strtoupper($string);}
    protected function lower($string)
    {return strtolower($string);}
    protected function capitalize($string) 
    { /* not ucwords. UTF8 unfriendly */
        return mb_convert_case($string, MB_CASE_TITLE, "UTF-8"); }
    protected function capfirst($string) 
    { /* not ucfirst. UTF8 unfriendly :(*/
       return strtoupper(substr($string, 0, 1)).substr(strtolower($string), 1);   }
    protected function space($string) 
    {return preg_replace("/\s{2,}/", ' ',$string);}
    protected function escape($string, $quotes = 1) 
    { return htmlspecialchars($string, $quotes ? ENT_QUOTES : ENT_NOQUOTES); }
	protected function unescape($string, $quotes = 1)
	{ return htmlspecialchars_decode($string, $quotes ? ENT_QUOTES : ENT_NOQUOTES); }
    protected function truncate ($string, $max = 50, $ends = '...') 
    {return substr($string,0,abs($max - strlen($ends))).$ends; }
    protected function nl2br($string) 
    {return nl2br($string);}
    protected function decode_ip($long_ip)
    {return long2ip($long_ip);}
    protected function append($string, $str2append, $insert_whitespace = false)
    {return $string.($insert_whitespace?"&nbsp;":"").$str2append;}
    protected function prepend($string, $str2prepend, $insert_whitespace = false)
    {return $str2prepend.($insert_whitespace?"&nbsp;":"").$string;}
    protected function plural($number, $key, $model = null)
    { if(!is_numeric($number) || !isset($number)) return $number;
       return Language::getPluralConst($number,$key, $model, $number); 
    }
	protected function stripslashes($str)
	{ return stripslashes($str); }
}
// }}}
?>
