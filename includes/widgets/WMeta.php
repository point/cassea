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


//
// $Id:$
//
WidgetLoader::load("WComponent");
//{{{ WMeta
class WMeta extends WComponent
{
    // {{{ parseParams
    /**
    * Method description
    *
    * More detailed method description
    * @param    array
    * @return void
    */
    function parseParams(SimpleXMLElement $elem)
	{
		$attr = array();
		if(isset($elem['http-equiv']))
			$attr['http-equiv'] = (string)$elem['http-equiv'];
		if(isset($elem['name']))
			$attr['name'] = (string)$elem['name'];
		if(isset($elem['content']))
			$attr['content'] = (string)$elem['content'];
		if(isset($elem['scheme']))
			$attr['scheme'] = (string)$elem['scheme'];

		if(!empty($attr['content']))  // content is required
			Header::get()->addMeta($attr);
		
		if(isset($elem['nofollow']) && (string)$elem['nofollow'])
			Header::get()->nofollow();

		if(isset($elem['noindex']) && (string)$elem['noindex'])
			Header::get()->noindex();

		$attr = array();

        if (isset ($elem['charset'])) $attr['charset'] = (string)$elem['charset'];
        if (isset ($elem['href'])) $attr['href'] = (string)$elem['href'];
        if (isset ($elem['hreflang'])) $attr['hreflang'] = (string)$elem['hreflang'];
        if (isset ($elem['type'])) $attr['type'] = (string)$elem['type'];
        if (isset ($elem['rel'])) $attr['rel'] = (string)$elem['rel'];
        if (isset ($elem['rev'])) $attr['rev'] = (string)$elem['rev'];
		if (isset ($elem['title'])) $attr['title'] = (string)$elem['title'];
		if (isset ($elem['cond'])) $attr['cond'] = (string)$elem['cond'];
		if (isset ($elem['media'])) $attr['media'] = (string)$elem['media'];

		if(!empty($attr))
			Header::get()->addLink($attr);
		
		
		parent::parseParams($elem);		    	
    }
    // }}}
}
//}}}
?>
