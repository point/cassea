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
require("../includes/Boot.php");

Boot::setupSession();
Autoload::addVendor("captcha");
$controller = Controller::getInstance();
$controller->registerStep(0);
$page = $controller->p2[0];
if(Config::getInstance()->captcha->type == 'static')
{
    $s=Storage::create("__CAPTCHALIST__",2592000);
    $filenames = array();
    if (!$s->is_set("files")) 
    {
        for($i=1;$i<=Config::getInstance()->captcha->dirs_count;$i++)
        {
            $filenames[$i] = array_map(create_function('$e','$pi = pathinfo($e);return $pi["filename"];'),
                glob(Config::get('root_dir').Config::getInstance()->captcha->dir."/{$i}/*.png"));
		}
        $s->set("files",$filenames);
    }
    $filenames=$s->get("files");
    $s->close();
    unset($s);
    $st=Storage::createWithSession("_CAPTCHA_",60);    
    $i=mt_rand(1,Config::getInstance()->captcha->dirs_count);
	$j=mt_rand(0,Config::getInstance()->captcha->files_count-1);
    $path="/captcha/{$i}/{$filenames[$i][$j]}.png";
    $st->set("answer",$filenames[$i][$j]);
    $st->set("page",$page);
    header("Content-Type:image/png");
    header("X-Accel-Redirect:".$path);
    exit();
}
else
{
    $str = null;
    $image=imagickCaptcha::generateCaptchaImg($str,Config::getInstance()->captcha->font_color,Config::getInstance()->captcha->background);
    $st=Storage::createWithSession("_CAPTCHA_",60);    
    $st->set("answer",$str);
    $st->set("page",$page);
    header('Content-Type:image/png');
    echo $image;
    exit();
}
?>
