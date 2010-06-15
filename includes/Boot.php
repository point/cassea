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
 * Cassea boot file. Require it to bootstrap all depended classes and files.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */


set_include_path('.');
/**
 * Require some common files and classes
 */
require("functions.php");
//require("exceptions.php");
//require("interfaces.php");
require("env/Loader.php");
require("Config.php");
require('Autoload.php');
require('ErrorsHandler.php');

/**
 * Setup errors and exceptions handlers.
 *
 * @see ErrorsHandler
 */ 
ErrorsHandler::setup();

/**
 * Init config with CONFIG and CONFIG_SECTION constants
 * If they are not defined, "config.ini" and "config" are used instead.
 * In current implementation use IniDBConfig only
 */
Config::init(new IniDBConfig());

/**
 * Init helper class to lookup and include classes. One class per file is allowed.
 * Name of the file must be the same as class name.
 */
Autoload::init();

/**
 * Connect to database. All parameters defined in configuration file
 */
DB::init(Config::getInstance()->db);

/**
 * Adjust timezone with defined in configuration file. If no tz inforamtion is given,
 * default tz will be used (configured in operation system of server).
 */
$tz = Config::get('timezone');
if(!empty($tz))
	date_default_timezone_set($tz);

/**
 * Boot is a helper class which allow to initialize essential but not commonly used subsystems,
 * like session, user, etc. In case of usual web application, {@link Boot::setupAll} may be used.
 * It initialize session, user, language with one method call.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */
class Boot
{
	/**
	 * Setting up session. Type of session and its behaviour defines in confuguration file. 
	 * @param null
	 * @return null
	 * @see Session::init
	 */
	static function setupSession()
	{
		Session::init();
	}
	/** 
	 * Setting up user subsystem. It contains info about user, who makes a request.
	 * @see User
	 */
	static function setupUser()
	{
        User::get();
	}
	/**
	 * Setting up language discovering and i18n subsystem. 
	 * @see Language
	 */
	static function setupLanguage()
	{
        Language::init();
	}
	
	/**
	 * Setting up plugins, that registered in the system.
	 * They will be looked up in /vendors/plugins/*.php and required in order of string sorting.
	 */
	static function setupPlugins()
	{
		foreach(t(new Dir(Config::get('root_dir'),true))->getDir(Config::get('vendors_dir'))
			->getDir('plugins')->ls('*.php') as $f)
			require_once($f);
	}

	/**
	 * Setting up session, user and language by one method call
	 */
	static function setupAll()
	{
		self::setupSession();
		self::setupUser();
		self::setupLanguage();
		self::setupPlugins();
	}
}
