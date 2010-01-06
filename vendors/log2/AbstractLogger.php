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
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */
//{{{ AbstractLogger
/**
 */
abstract class AbstractLogger implements iLog2Formattable
{
	protected 
		$predefined_vars = array(),
		$format = '$date $time $log_level $message'
			;

	function __construct()
	{
		$this->predefined_vars = array(
			"http_host"=>$_SERVER['HTTP_HOST'],
			"http_user_agent"=>$_SERVER['HTTP_USER_AGENT'],
			"http_referer",
			"http_via",
			"http_x_forwarded_for", 
			"http_cookie"=>$_SERVER['HTTP_COOKIE'], 
			"remote_addr"=>$_SERVER['REMOTE_ADDR'],
			"remote_port"=>$_SERVER['REMOTE_PORT'],
			"server_addr"=>$_SERVER['SERVER_ADDR'],
			"server_port"=>$_SERVER['SERVER_PORT'],
			"server_protocol"=>$_SERVER['SERVER_PROTOCOL'],
			"uri"=>$_SERVER['REQUEST_URI'],
			"request_method"=>$_SERVER['REQUEST_METHOD'],
			"hostname"=>$_SERVER['hostname'],
			"rfc_date"=>date(DATE_RFC822),
			"date"=>date("j M Y"),
			"time"=>date("G:i:s")
		);	
	}
	public function setFormat($format)
	{
		if(!empty($format))
			$this->format = $format;
	}
	protected function formatString(array $params)
	{
		extract($this->predefined_vars+$params);
		return @preg_replace('/(\$[A-Za-z][A-Za-z0-9]{0,})/e',"\\1",$this->format);
	}
}
//}}}
