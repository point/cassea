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
$controller='index';
unset($_GET['__q']);
list($rewrite) = explode("?",$_SERVER['REQUEST_URI']);
if(preg_match('#^(/[^\.?&]*[^/])([?&\#].*)?$#',$rewrite,$match))
    $rewrite = $match[1]."/".$match[2];
if(preg_match('#^/([a-z]{2})(/.*)$#',$rewrite,$match))
{
    $_GET['__lang'] = $match[1];
    $rewrite = $match[2];
}

//controller
if(preg_match('#^/([^/\.]{3,})(/.*)?$#',$rewrite,$match))
    $controller = $match[1];
else $rewrite = "/".$controller.$rewrite;
	
if(preg_match('#^/([^/\.]{1,2})/.*$#',$rewrite,$match))
    {header("HTTP/1.0 404 Not found");exit();}

$_GET['__p1'] = "index.xml";
$_GET['__p2'] = "";
if(preg_match('#^/([^/\.]{3,})(/([^\.]+))?(/([^/]+)\.(htm|html|xml)?)?$#',$rewrite,$match)){
	if(isset($match[5]))
		$_GET['__p1'] = $match[5];
	if(isset($match[3]))
		$_GET['__p2'] = $match[3];
}
//make env for scripts
$_SERVER['PHP_SELF'] = "/controllers/$controller.php";
set_include_path(realpath($_SERVER['DOCUMENT_ROOT'])."/controllers/". PATH_SEPARATOR . get_include_path());
chdir(realpath($_SERVER['DOCUMENT_ROOT'])."/controllers/");

$c_name = dirname(__FILE__)."/controllers/".$controller.".php";
if(!file_exists($c_name))
    {header("HTTP/1.0 404 Not found");exit();}

require_once($c_name);
