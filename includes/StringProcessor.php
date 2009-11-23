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

/**
 * This file contains class for manipulating strings, passed 
 * directly from the widgets and XML file.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id:$
 * @package system
 * @since 
 */

//{{{ StringProcessor 
/**
 * This class intended for simple string manipulation, which is not
 * a model's business. For example, date representation manipulation.
 *
 * Defining <code> process="trim|relative_date" </code>
 * in XML file, StringProcessor will be created and 
 * and "trim" and "relative_date" processors will be registered.
 * Calling <code> process($string) </code> will change given $string
 * according to the registered processors.
 *
 * Due to this class extends EventBehaviour it might be mixing up with
 * various user's functions, installed as a plug-in.
 *
 * All processor functions must have at least 1 parameter -- string to be
 * processed. If it has more than 1 parameter, other must be marked as optional:
 * <pre><code>
 * publi function another_processor($string, $parameter1 = null, $parameter2 = null) {}
 * </code></pre>
 */
class StringProcessor extends EventBehaviour
{
    protected
		/**
		 * Registered processors methods.
		 * @var array
		 */
        $processors = array()
        ;

	//{{{ addProcessor
	/**
	 * Adds specified processor to the process sequence.
	 * Additionally, array of optional parameters might be 
	 * passed to the processor method.
	 *
	 * @param string name of the string processor method
	 * @param array of optional parameters
	 * @return null
	 */
    function addProcessor($name,$parameters = array())
    {
        if(!is_string($name) || empty($name)) return;
        $this->processors[] = array("name"=>$name,"parameters"=>$parameters);
	}
	//}}}

	//{{{ process
	/**
	 * Executes registered processor methods upon the given string.
	 * If there is no such method in this class or in plug-ins, 
	 * no changes will be maid (silently).
	 *
	 * @param string to be transformed
	 * @return string processed string 
	 */
    function process($string)
    {
        if(empty($this->processors) || $string === "") return $string;
        $s = $string;
        foreach($this->processors as $p)
        {
            $params = $p['parameters'];
            array_unshift($params,$s);
            if(method_exists($this,$p['name']) || isset($this->behaviours[$p['name']]))
                $s = call_user_func_array(array($this,$p['name']),$params);
        }
        return $s;
    }
	//}}}

	//{{{ Built-in processors

	//{{{ truncate_words
	/**
	 * If the given string length exceeds value, passed via $limit parameter,
	 * it will be wrapped to the length, not grater than $limit,
	 * with preserving words. So, no one of the words will be broken in the middle.
	 *
	 * @param string to be transformed
	 * @param int maximum string length in chars (Default is 50)
	 * @param string string to be added at the end (Default is "...")
	 * @return string processed string
	 */
    protected function truncate_words($string, $limit = 50, $ends = '...') 
    {
		$string = trim($string);
        if (($to = strlen($string)) <=  $limit) return $string;

        $pos = str_word_count_utf8($string, 2);

		foreach($pos as $k=>$v)
			if($k >= $limit)
			{$to = $k;break;}

        return (substr($string, 0, $to-1).$ends);
    }
	//}}}

	//{{{ filesize
	/**
	 * Treats given string as a numeric value, that represents 
	 * file size in bytes. Returns pretty string, showing 
	 * file size in KB, MB etc. 
	 * If $flag is in not-false state, single whitespace between 
	 * numbers and units will be placed. For example:
	 * <pre><code>
	 * 1048576 => 1MB
	 * </code></pre>
	 * @param numeric file size
	 * @param bool to place or not whitespace before unit
	 * @return string processed string
	 */
    protected function filesize($size, $flag=0){
        return sizeToString($size,$flag);
	}
	//}}}

	//{{{ nl2pbr
	/**
	 * Wraps pieces of given string, separated by 2 or more new-lines 
	 * with '<p>' tag. Inner single new-line characters will be 
	 * converted to "<br />"-s. For example, the string:
	 * <pre><code>
	 * Paragraph1.
	 * Simple line1.
	 *
	 * Paragraph2.
	 * Simple line2.
	 * </code></pre>
	 * will be converted to:
	 * <pre><code>
	 *	<p>Paragraph1.<br/>Simple line1.</p><p>Paragraph2.<br/>Simple lin2</p>
	 * </code></pre>
	 * @param string to be transformed
	 * @return string processed string
	 */
    protected function nl2pbr($value) 
    {
        $result = array();
        foreach (preg_split('/(\r?\n){2,}/m', $value) as $part) 
            $result[] = '<p>' . nl2br(trim($part)) . '</p>';
        
        return implode("\n", $result);
	}
	//}}}

	//{{{ date
	/**
	 * Calls standard PHP function date with given format (default is 'jS F Y H:i'),
	 * converting to unixtimestamp if needed.
	 * 
	 * @param mixed value to be converted to time
	 * @return string processed string
	 */
    protected function date($time, $format = 'jS F Y H:i') 
	{
        return date($format, is_numeric($time)?$time:strtotime($time));
	}
	//}}}

	//{{{ date_locale
	/**
	 * Outputs date considering given locale. By default system-wide locale will be taken.
	 * Internally, this processor calls strftime PHP function.
	 * Default format of output is "%e %B %Y, %R:%M".
	 * If $time is not numeric, trying to convert to unixtimestamp with
	 * strtotime function.
	 * 
	 * @param mixed value to be converted to localized time
	 * @param string format of outputted string
	 * @param string locale to be used
	 * @return string processed string
	 */
    protected function date_locale($time,$format ="%e %B %Y, %R:%M", $locale = null)
	{
		$ret = "";
		if(isset($locale))
		{
			$prev = setlocale(LC_TIME,0);
			setlocale(LC_TIME,$locale);
			$ret = strftime($format, is_numeric($time)?$time:strtotime($time));
			setlocale(LC_TIME,$prev);
		}
		else
			$ret = strftime($format, is_numeric($time)?$time:strtotime($time));
		return $ret;
    }
	//}}}

	//{{{ _plural
	/**
	 * Internal function.
	 * It takes a number and a base part of the key in
	 * messages. And returns plural representation of given
	 * key. 
	 * All keys are searched in messages/widgets.<lang>.php file.
	 *
	 * For example, for (11,day) for English lang will return
	 * "days" string to be used as "11 days".
	 *
	 * @param numeric number
	 * @param string base part of the key
	 * @return string
	 */
    protected final function _plural($number, $lang_const_base)
	{
        return Language::message('widgets',Language::getPluralKey($number,$lang_const_base));
    }
	//}}}

	//{{{ relative_time
	/**
	 * Returns human-friendly time, relative to the current timestamp in given locale.
	 *
	 * @param mixed numeric (represented unix-timestamp) or string, which 
	 * will be converted to timestamp with strtotime
	 * @return string relative time
	 */
    protected function relative_time($time, $locale = null) 
	{
		$prev = null;
		if(isset($locale))
		{
			$prev = setlocale(LC_TIME,0);
			setlocale(LC_TIME,$locale);
		}
        $timestamp = is_numeric($time)?$time:strtotime($time);
        $time   = mktime(0, 0, 0);
		$delta  = time() - $timestamp;
		//to prevent case "in 0 seconds"
		if($delta == 0) $delta = 1;
        $string = '';
        
        if ($timestamp < $time - 86400) 
		{ 
			$ret = strftime("%B %e, %Y, %R:%M", $timestamp);
			if($prev !== null)
				setlocale(LC_TIME,$prev);
			return $ret;
		}

        if ($delta > 86400 && $timestamp < $time) 
		{
			$ret = Language::message('widgets',"Yesterday_at")." " .strftime("%R:%M", $timestamp);
			if($prev !== null)
				setlocale(LC_TIME,$prev);
			return $ret;
		}

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
        $ret = ($delta > 0)?($string." ".Language::message('widgets',"ago")):(Language::message('widgets',"date_in")." ".$string);
		if($prev !== null)
			setlocale(LC_TIME,$prev);
		return $ret;
    }
	//}}}

	//{{{ relative_date
	/**
	 * Returns human-friendly date, relative to the today's date in given locale
	 *
	 * @param mixed numeric (represented unix-timestamp) or string, which 
	 * will be converted to timestamp with strtotime
	 * @return string relative date
	 */
    protected function relative_date($time,$locale = null) 
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
            
		$prev = null;
		if(isset($locale))
		{
			$prev = setlocale(LC_TIME,0);
			setlocale(LC_TIME,$locale);
		}
        
        if (abs($reldays) < 182) 
            $ret = strftime("%A, %B %e",$time ? $time : time());
         else 
             $ret = strftime("%A, %B %e, %Y",$time ? $time : time());

		if($prev !== null)
			setlocale(LC_TIME,$prev);
        return $ret;
    }
	//}}}
    
	//{{{ relative_datetime
	/**
	 * Returns relative_time if difference between now and given time
	 * less than 24 hours. Otherwise returns relative_date
	 *
	 * @param mixed numeric (represented unix-timestamp) or string, which 
	 * will be converted to timestamp with strtotime
	 * @return string relative time or date
	 */
    protected function relative_datetime($time, $locale = "ru_RU.UTF8") 
    {
        $date = self::relative_date($time,$locale);
        if ($date == Language::message('widgets',"today")) 
            return self::relative_time($time,$locale);
        return $date;
    }
	//}}}

	//{{{ md5
	/**
	 * Returns md5 hash of given string
	 * @param string 
	 * @return string md5 hash
	 */
	protected function md5($string)
    {return md5($string);}
	//}}}
	
	//{{{ sha1
	/**
	 * Returns sha1 hash of given string
	 *
	 * @param string
	 * @return string sha1 hash
	 */
    protected function sha1($string)
    {return sha1($string);}
	//}}}

	//{{{ wordwrap
	/**
	 * Calls PHP standard function wordwrap with specified parameters
	 *
	 * @param string
	 * @return string wrapped string
	 */
    protected function wordwrap($string, $width = 75, $break = "\n", $cut = false)
	{return wordwrap($string,$width,$break,$cut);}
	//}}}

	//{{{ trim
	/**
	 * Trims whitespaces in given string
	 *
	 * @param string
	 * @return string trimmed string
	 */
    protected function trim($string)
	{return trim($string);}
	//}}}

	//{{{ upper
	/**
	 * Returns strtoupper-ed string
	 *
	 * @param string
	 * @return string processed string
	 */
    protected function upper($string)
	{return strtoupper($string);}
	//}}}

	//{{{ lower
	/**
	 * Returns strtolower-ed string
	 *
	 * @param string
	 * @return string processed string
	 */
    protected function lower($string)
	{return strtolower($string);}
	//}}}

	//{{{ capitalize
	/**
	 * Returns string with capitalized words.
	 *
	 * @param string
	 * @return string processed string
	 */
    protected function capitalize($string) 
    { /* not ucwords. UTF8 unfriendly */
		return mb_convert_case($string, MB_CASE_TITLE, "UTF-8"); 
	}
	//}}}

	//{{{ capfirst
	/**
	 * Returns string where the first word is capitalized.
	 *
	 * @param string
	 * @return string processed string
	 */
    protected function capfirst($string) 
    { /* not ucfirst. UTF8 unfriendly :(*/
		return strtoupper(substr($string, 0, 1)).substr(strtolower($string), 1);   
	}
	//}}}

	//{{{ space
	/**
	 * Returns string with compressed whitespaced.
	 *
	 * @param string 
	 * @return string processed string
	 */
    protected function space($string) 
	{return preg_replace("/\s{2,}/", ' ',$string);}
	//}}}

	//{{{ escape
	/**
	 * Escapes given string. If flag $quotes is true, additionally quotes will be 
	 * escaped.
	 *
	 * @param string
	 * @param bool indicates whenever to escape quotes
	 * @return string processed string
	 */
    protected function escape($string, $quotes = 1) 
	{ return htmlspecialchars($string, $quotes ? ENT_QUOTES : ENT_NOQUOTES); }
	//}}}

	//{{{ unescape
	/**
	 * Decodes previously escaped string. 
	 * If flag $quotes is true, than quotes will be decoded additionally.
	 *
	 * @param string
	 * @param bool indicates whenever to decode quotes
	 */
	protected function unescape($string, $quotes = 1)
	{ return htmlspecialchars_decode($string, $quotes ? ENT_QUOTES : ENT_NOQUOTES); }
	//}}}

	//{{{ truncate
	/**
	 * Returns string with maximum length equal to $max.
	 * The string might be broken on the middle on the word.
	 * To prevent this, use {@link limitwords} instead.
	 *
	 * String $ends will be added to the end of resulting string.
	 * @param string
	 * @param int maximum number of chars in string
	 * @param string to be added to the end
	 * @return string processed string
	 */
    protected function truncate ($string, $max = 50, $ends = '...') 
	{  if (strlen($string) <=  $max) return $string;
		return substr($string,0,abs($max - strlen($ends))).$ends; }
	//}}}
	
	//{{{ nl2br
	/**
	 * Returns string, where all newlines are replaced with <br/>'s.
	 *
	 * @param string
	 * @return string processed string
	 */
    protected function nl2br($string) 
    {return nl2br($string);}
	//}}}

	//{{{ decode_ip
	/**
	 * Decode IP address from long-base representation to
	 * the human-readable string
	 *
	 * @param string
	 * @return string processed string
	 */
	protected function decode_ip($long_ip)
	{return long2ip($long_ip);}
	//}}}

	//{{{ append
	/**
	 * Appends to the end of the given string another specified string.
	 *
	 * If $insert_nbsp flag is equal to true, "&nbsp;" will be added before
	 * concatenated string.
	 *
	 * @param string
	 * @param string that need to be added
	 * @param bool indicates whenever to insert nbsp entity.
	 * @return string
	 */
    protected function append($string, $str2append, $insert_nbsp = false)
    {return $string.($insert_nbsp?"&nbsp;":"").$str2append;}
	//}}}

	//{{{ prepend
	/**
	 * Prepend to the beginning of the given string another specified string.
	 *
	 * If $insert_nbsp flag is equal to true, "&nbsp;" will be added after
	 * prepended string.
	 *
	 * @param string
	 * @param string that need to be prepended
	 * @param bool indicates whenever to insert nbsp entity.
	 * @return string
	 */
    protected function prepend($string, $str2prepend, $insert_whitespace = false)
	{return $str2prepend.($insert_whitespace?"&nbsp;":"").$string;}
	//}}}

	//{{{ plural
	/**
	 * Returns plural representation of the string, which could be found
	 * by the $key in the language-constants space of the given model.
	 *
	 * @param numeric  
	 * @param string key
	 * @param string model name
	 * @return string
	 */
    protected function plural($number, $key, $model = null)
    { if(!is_numeric($number) || !isset($number)) return $number;
       return Language::getPluralConst($number,$key, $model, $number); 
    }
	//}}}

	//{{{ stripslashes
	/**
	 * Calls standard PHP function stripslashes on the 
	 * given string.
	 *
	 * @param string
	 * @return string processed string
	 */
	protected function stripslashes($str)
	{ return stripslashes($str); }
	//}}}

	//{{{ markdown
	/**
	 * Returns string, processed by the markdown module
	 *
	 * @param string
	 * @return string processed string
	 */
	protected function markdown($str)
	{ return Markdown($str); }
	//}}}

	//{{{ whitespace2nbsp
	/**
	 * Replaces all whitespaces with "&nbsp;" entity.
	 *
	 * @param string
	 * @return string processed string
	 */
	protected function whitespace2nbsp($str)
	{ return str_replace(" ",'&nbsp;',$str); }
	//}}}

	//}}}
}
//}}}
?>
