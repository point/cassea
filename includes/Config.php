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

class Config
{
	const ROOT_DIR = "/usr/local/www/devel";

	const STORAGE_ENGINE = "memcache";
//	const STORAGE_ENGINE = "filesystem";

    const MEMCACHED_HOST = 'localhost';
    const MEMCACHED_PORT = '11211';

    const STORAGE_PATH = "/cache/storage";

    const SESSION_ENGINE='memcache';
    //const SESSION_ENGINE='database';

    const SESSION_LENGTH=3600; //length of server-side session
    const SESSION_COOKIE_LENGTH = 315360000; // 10 years

    /*** User ***/ 
    //time to cache some user profile data
    const USER_PROXY_TIME = 86400; // 0 - disabled; 
    const USER_SECRET = 'It may or may not be worthwhile, but it still has to be done.';
	
	const XMLPAGES_PATH = "/pages";

	const IMAGES_PATH = "/usr/local/www/devel/web/images";
	const USE_IMAGES_CACHE = true;

	const JS_VER = "0.1";
	const CSS_VER = "0.1";

	const CACHE_STATIC_PAGES = true;

	const HTML_DIR="/usr/local/www/devel/web/html";

    // MAILER
    const MAIL_TRANSPORT = 'smtp';
    //const MAIL_TRANSPORT = 'mail';
    //const MAIL_TRANSPORT = 'sendmail';
    
    //const MAIL_DEFAULT_FROM = 'postmaster@intvideo';
    //const MAIL_DEFAULT_FROM_NAME = 'postmaster';
    const MAIL_DEFAULT_FROM = 'climbonn@gmail.com';
    const MAIL_DEFAULT_FROM_NAME = 'Алексей Ковтунец';


    //const MAIL_SMTP_HOST = 'smtp.intvideo';
    //const MAIL_SMTP_PORT = '25';
    const MAIL_SMTP_HOST = 'smtp.gmail.com';
    const MAIL_SMTP_PORT = '25';

    //const MAIL_SMTP_PORT = '465'; //ssl

    const MAIL_SMTP_PROTO = ''; // usual tcp
    //const MAIL_SMTP_PROTO = 'ssl'; // ssl and 465 port
    
    //const MAIL_SMTP_USER = 'postmaster@intvideo';
    //const MAIL_SMTP_PASSWD = 'postmasterpass';
    const MAIL_SMTP_USER = 'climbonn';
    const MAIL_SMTP_PASSWD = 'climbonsight';
    const MAIL_SENDMAIL_PATH = '/usr/sbin/sendmail';

    //class.mailObject DONT FORGET TO CHAGE REGEXP!!!!!!!!!

	static function get($var)
	{
		if(defined('self::'.$var))
			return constant('self::'.$var);
		return null;
	}
}
?>
