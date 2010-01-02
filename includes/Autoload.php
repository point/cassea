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
 * This file contains class for automating of class loading.
 *
 * @author billy <alexey.mirniy@gmail.com>
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

//{{{ Autoload
/**
 * Autoload unifies and automatize mechanism for requiring files and classes.
 * It's built on spl_autoload_register function.
 * By convention, in cassea one file must contains one class with the same name.
 * 
 * Base core classes located at <root_dir>/includes directory.
 * Classes, related to user identification, such as User, UserManager, Profile, Session etc
 * located at <root_dir>/includes/user directory.
 * Classes, with mail sender located at <root_dir>/includes/mailer
 * Classes, which help to do routine work with filesystem located at <root_dir>/includes/fs
 * Files with settings for environments located at <root_dir>/includes/env
 *
 * Thees directories and <root_dir>/<vendor_dir>/includes are automatically added to the look-up list.
 * Here, <root_dir> defines automatically and point to the root of project's directory hierarchy.
 * The vendor dir is used for third party extensions and path to it defines in config file.
 * See {@link addVendor} for more information.
 *
 */
class Autoload
{
	/**
	 * @var array dirs in which to make look up
	 */
    private static $dirs = array();

	/**
	 * @var string cached root dir value
	 */
	private static	$rd = null,
	/**
	 * @var string cached vendor dir value
	 */
		$vd = null,
	/**
	 * @var string cached models dir value
	 */
		$md = null
	;

	//{{{ init
	/**
	 * This method initialize Autoload class. 
	 * 
	 * It defines method to call to find given class and register several system critical paths.
	 * It must be called in in the early stages of booting the application.
	 * 
	 * @param null
	 * @return null
	 * @see Boot
	 */
    public static function init(){
        spl_autoload_register('Autoload::load');

        $c=  Config::getInstance();
        self::$rd = $c->root_dir;
        self::$vd = self::$rd.$c->vendors_dir;
        self::$md = self::$rd.$c->models_dir;
		
		self::addDir(self::$rd.'/includes');
		self::addDir(self::$rd.'/includes/user');
		self::addDir(self::$rd.'/includes/fs');
		self::addDir(self::$rd.'/includes/exceptions');
		self::addDir(self::$rd.'/includes/interfaces');
		self::addDir(self::$rd.'/includes/mailer');

		//self::addDir(self::$rd.'/includes/widgets');

        self::addDir(self::$vd.'/includes');
	}
	//}}}

	//{{{ addDir
	/**
	 * Adds the given path to look-up list.
	 * This method must be used for raw pushing the path only.
	 * 
	 * @param string absolute path to directory which should be added
	 * @return null
	 * @throws AutoloadException if directory doesn't exists
	 * @see addVendor
	 */

    public static function addDir($path){
        $dir = is_file($path)?dirname($path):rtrim($path,"/");
		if(!is_dir($dir))
			throw new AutoloadException("Given directory '$path' doesn't exists");

        if (!in_array($dir, self::$dirs)) array_push(self::$dirs, $dir);
    }
	//}}}

	//{{{ getDirs
    /**
	 * Returns the look-up list for the current moment.
     *
	 * @param null
	 * @return array enumerated list of paths
     */
    public static function getDirs(){
        return self::$dirs;
    }
	//}}}

	//{{{ addVendor
	/**
	 * Convert given parameters to canonical paths and adds they to look-up list.
	 * By convention, in cassea vendor can choose 2 strategies for files disposition.
	 * <ol>
	 * <li>
	 * 
	 * Simple: all files are located in one directory - the plugin directory.
	 * For example <code>/vendors/feed</code> . This folder will be added to look-up list. 
	 * If e.g. <pre><code>Feed::create()</code></pre> is called, file with name 
	 * <code>Feed.php</code> will be searched in this directory. In case when this 
	 * behaviour doesn't feet to developer's needs, he may create file <code>autoload.php</code>
	 * in the plugin root (<code>/vendors/feed</code>) and define there 
	 * <code>require()</code> or <code>spl_autoload_register()</code>
	 * constructions to properly include PHP files in non-trivial directory hierarchy.
	 * 
	 * Use <code>Autoload::addVendor(_plugin_name_)</code> (<code>Autoload::addVendor("feed")</code>) 
	 * to add to look-up list.
	 * 
	 * </li>
	 * <li>
	 * 
	 * More complex one. Several plugins are conjucted by one vendor name or role.
	 * Cassea ships with several plugins that matches this type.
	 * For example, <code>/vendors/session</code>. We propose third-party developers to host their 
	 * session-related plugins in this directory.
	 * 
	 * As in the first case, you can use trick with <code>autoload.php</code>, e.g.
	 * <code>/vendors/session/autoload.php</code>.
	 * 
	 * Use <code>Autoload::addVendor(_group_name_ , _plugin_name_)</code> 
	 * (<code>Autoload::addVendor("session","database")</code>) to add to look-up list.
	 * 
	 * @param string name of plugin or group name
	 * @param mixed plugin name in second case. Can be either a string or null.
	 * @retrun null
	 * @throws AutoloadException with error message if somthing goes wrong
	 */
	public static function addVendor($name1, $name2 = null)
	{
		if(!isset($name1) && !isset($name2)) throw new AutoloadException('Vendor name must be specified');
		$_d = self::$vd."/$name1";
		if(!is_dir($_d))
			throw  new AutoloadException("Directory $_d doesn't exists");
		if(!is_readable($_d))
			throw new AutoloadException("Directory $_d is not readable");


		// if $name1 -- dir with target classes. Adding it to lookup
		// directories list
		if(empty($name2))
		{
			if(count(glob("$_d/*")) != count(($dirs = glob("$_d/*",GLOB_ONLYDIR))))
				if(file_exists("$_d/autoload.php"))
					require_once("$_d/autoload.php");
				else 
					self::addDir($_d);
			else 
				foreach($dirs as $_dir)
					self::addDir("$_d/$_dir");
		}
		elseif(is_dir("$_d/$name2") && is_readable("$_d/$name2"))
			if(file_exists("$_d/$name2/autoload.php"))
				require_once("$_d/$name2/autoload.php");
			else
				self::addDir("$_d/$name2");
		else throw new AutoloadException("Directory $_d/$name2 doesn't exists or not readable");
	}
	//}}}
	
	//{{{ addModel
	/**
	 * Convert given parameters to canonical paths and adds they to look-up list.
	 *
	 * Use <code>Autoload::addVendor(_group_name_ , _plugin_name_)</code> 
	 * (<code>Autoload::addVendor("session","database")</code>) to add to look-up list.
	 * 
	 * @param string model name
	 * @retrun null
	 * @throws AutoloadException with error message if somthing goes wrong
	 */
	public static function addModel($name)
	{
		if(!isset($name)) throw new AutoloadException('Models name must be specified');
		$_d = self::$md."/".$name;
		if(!is_dir($_d))
			throw  new AutoloadException("Directory $_d doesn't exists");
		if(!is_readable($_d))
			throw new AutoloadException("Directory $_d is not readable");

		if(file_exists("$_d/autoload.php"))
			require_once("$_d/autoload.php");
		else 
			self::addDir($_d);
	}
	//}}}
	
	//{{{ ===DEPREACTED===
	/* deprecated */
	public static function addVendorDir($name1, $name2 = null)
	{
		$b = debug_backtrace();
		trigger_error("addVendorDir is depricated. Use addVendor instead at ".$b[0]['file']." line ".$b[0]['line']);
		return self::addVendor($name1, $name2);
	}
	//}}}

	//{{{ load
	/**
	 * This method automatically adds <code>spl_autoload_register()</code> to 
	 * find and load nonexistent classes. 
	 *
	 * Usually, not used by end user.
	 *
	 * @param string name of the class to load
	 * @return null
	 */
    public static function load($class){
		
		if(class_exists($class,false)) return;

        foreach(self::$dirs as $d)
            if (is_file($f= $d.'/'.$class.'.php') && is_readable($f))
				return require_once($f);
		
		if (class_exists(AutoloadException, false)){
			$ex = new AutoloadException('Class "'.$class.'" not found');
			$ex->setExtra('Relative paths', implode(', ',str_replace(self::$rd.'/', '' ,self::$dirs)));
			throw $ex; 
		}
		else
			die('Class "'.$class.'" not found'.PHP_EOL.'Relative paths: '. implode(', ',str_replace(self::$rd.'/', '' ,self::$dirs)));
}
	//}}}
}
//}}}
