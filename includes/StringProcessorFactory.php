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
 * This file contains class for managing and check user's rights.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

//{{{ StringProcessorFactory
/**
 * This class used to retrieve StringProcessor to process 
 * text.
 * 
 * It might be used from XML file, processing text at the last 
 * phase of rendering. 
 * It also supports pipe-processing. Arguments which should be
 * passed to StringProcessor are specified, delimiting by 
 * whitespaces.
 *
 * For example:
 * <pre><code>
 * <WText process="trim | append 'qwe qwe' true"> Text </WText>
 * </code></pre>
 *
 * Will return string " Text ", trimmed and with append string
 * "qwe qwe" with preceding string "qwe qwe" and a whitespace. 
 * So, the result will be: "qwe qwe Text".
 *
 * Holden StringProcessor might be extended by plugins. 
 * Typical use-case is:
 * <pre><code>
 * StringProcessorFactory::getInstance()->foo = create_function(......);
 * </code></pre>
 * So, you can use it in XML.
 * <pre><code>
 * <WText process="trim | foo"> Text </WText>
 * </code></pre>
 *
 */
class StringProcessorFactory
{
	/**
	 * Cached singleton instance of StringProcessor
	 */
	static protected $instance = null;

	//{{{ getProcessorInstance
	/**
	 * Sigleton method, returning StringProcessor instance.
	 * @param null
	 * @return StringProcessor instance
	 */
	static function getProcessorInstance()
	{
		if(!isset(self::$instance))
			self::$instance = new StringProcessor();
		return self::$instance;
	}
	//}}}

	//{{{ create
	/**
	 * Creates and configure StringProcessor instance 
	 * with the processors, represented by the $str.
	 *
	 * Passing string "true" or "null" as a parameter will be converted 
	 * to PHP true and PHP null values respectively.
	 *
	 * @param string piped processor. I.e "trim | append 'qwe qwe' true"
	 * @return StringProcessor instance for futher processing
	 */
    static function create($str)
    {
        $o = clone self::getProcessorInstance();
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
            foreach($p2 as &$v) if($v === "null") $v = null; elseif($v === "true") $v = true;
            $o->addProcessor($p2[0],array_slice($p2,1));
        }
        return $o;
    }
	//}}}
}
//}}}
