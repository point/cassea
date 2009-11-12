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
 * This file contains classes for accessing to configuration values.
 * Here is the class for reading config from ini file 
 * and class for mixed storage: in database plus ini file.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

//{{{ ConfigException
/**
 * Exception for Config's classes. 
 * It's not derived from CasseaException because at the moment of
 * declaration, Autoload isn't inited.
 */
class ConfigException extends Exception {}
//}}}

//{{{ ConfigBase
/**
 * Base class for other config files. 
 * It simply parse and store in hierarchical view as array of netsted ConfigBase objects.
 * It allows to use namespaces in config values. For example, <code>mail->default_from</code>. 
 * Here <code>mail</code> is namespaces for sets of configuraton flags, which twealk various aspects 
 * of mail subsystem behaviour.
 *
 * Mostly used internaly by other derived classes.
 */
class ConfigBase implements IteratorAggregate
{
    protected 
		/**
		 * @var array stores internal hierarchical data
		 */
		$data = array();
			 
	//{{{ __construct
	/**
	 * Constructs new instance. Converts input array of plain values to hierarchy of objects.
	 * 
	 * @param array data to be stored in ConfigBase. 
	 * It should be array of scalar or array of nested array with scalars and so on.
	 * @return null
	 * @see parseArray
	 */
    protected function __construct(array $array = array())
    {
		$this->data['root_dir'] = dirname(dirname(__FILE__));
		$this->parseArray($array);
	}
	//}}}

	//{{{ parseArray
	/**
	 * Internal method to perform transformation of array as in {@link __construct} to 
	 * hierarchy of ConfigBase objects.
	 * Transformation needs to use fluent interfaces, such as <code>mail->default_from</code>.
	 *
	 * @param array of scalar or array of nested array with scalars and so on. 
	 * Non scalar values will be converted to string.
	 * @return null
	 */
	protected final function parseArray(array $array = array())
	{
        foreach ($array as $key => $value) 
            if (is_array($value)) 
                $this->data[$key] = new self($value);
             else
                $this->data[$key] = (string)$value;
	}
	//}}}

	//{{{ get
	/**
	 * Retrieves variable or ConfigBase object by name. 
	 * This method is called internally by Config class-facade.
	 *
	 * @param string  config value name
	 * @return mixed config value. It can be either string or ConfigBase instance.
	 * @throws ConfigException if given config values doesn't exists
	 * @see Config
	 * @see Config::getInstance
	 * @see Config::get
	 */
    function get($name)
    {
        $name = strtolower($name);
        if(isset($this->data[$name]))
            return $this->data[$name];
        else
            throw new ConfigException("Config value ".$name." doesn't exists");
	}
	//}}}

	//{{{ __get
	/**
	 * Magick method to retrieve variable or ConfigBase object by name.
	 * It can be used as convinient alternative to method {@link get}.
	 *
	 * @param string config value name
	 * @return mixed config value. It can be either string or ConfigBase instance
	 * @throws ConfigException if given config values doesn't exists
	 */
    function __get($name)
    {
        return $this->get($name);
    }
	//}}}


	//{{{ __set
	/**
	 * Magick method to store config value. It stores only in memory for particular request 
	 * and will be destroyed when script ends.
	 *
	 * @param string name of the config value
	 * @param mixed value to store in config. It can be either scalar or array or ConfigBase instance.
	 * Scalar and array value will be converted using {@link parseArray}, ConfigBase instances will be 
	 * assigned directly.
	 * @return null
	 * @throws ConfigException with error message explains the exceptional situation.
	 */
    function __set($name, $value)
	{
		if(!isset($name) || !is_scalar($name))
			throw new ConfigException("Wrong name for config value");
		if(!is_scalar($value) && !is_array($value) && !$value instanceof ConfigBase || !isset($value))
			throw new ConfigException("Wrong value type. Scalar and arrays are allowed");

		if($value instanceof ConfigBase)
			$this->data[$name] = $value;
		else
            $this->parseArray(array($name=>$value));
    }
	//}}}

	//{{{ __isset ===DEPREACATED===
	/**
	 * @deprecated
    function __isset($name)
    {
        return isset($this->data[$name]);
	}
	 */
	//}}}

	//{{{ __unset
	/**
	 * Unsets config value or whole namespace if exists. 
	 * It occurs in memory for current request only. 
	 * While processing next request theese values won't be deleted.
	 *
	 * @param string config value name or namespace name
	 * @return null
	 */
    function __unset($name)
    {
        if(isset($this->data[$name]))
            unset($this->data[$name]);
	}
	//}}}
	
	//{{{ toArray
	/**
	 * Convert inner object representation of current ConfigBase instance 
	 * back to arrays form. 
	 * This function is opposit to {@link parseArray}.
	 *
	 * @param null
	 * @return array converted representation in arrays form
	 * @see parseArray
	 */
    protected final function toArray()
    {
        $array = array();
        foreach ($this->data as $key => $value)
            if ($value instanceof ConfigBase) 
                $array[$key] = $value->toArray();
             else 
                $array[$key] = $value;

        return $array;
    }
	//}}}
	
	//{{{ getIterator
	/**
	 * Return iterator on internal data.
	 *
	 * @param null
	 * @return iterator on internal data.
	 */
	function getIterator()
	{
		return t(new ArrayObject($this->data))->getIterator();
	}
	//}}}

	//{{{ merge
	/**
	 * Merges current data structure with structure, passed as argument.
	 * It could be as object represented plain structure with key=>values, and 
	 * hierarchical structure. Inner data of current object will be overwritten by
	 * newly passed values.
	 *
	 * @param ConfigBase values to merge with inner data
	 * @return ConfigBase object what have been merged with passed parameter
	 */
    function merge(ConfigBase $merge)
    {
		foreach($merge as $key => $item) 
			if(isset($this->data[$key]))
                if($item instanceof ConfigBase && $this->$key instanceof ConfigBase) 
                    $this->$key = $this->$key->merge($item);
                 else 
                    $this->$key = $item;
                
             else 
                 $this->$key = $item;
            
		return $this;
    }
	//}}}
}
//}}}

//{{{ IniConfig
/**
 * Reads data from ini-structred file and parses into internal hierarchical representation.
 * Ini file may have some extended features, like inheritance of sections and namespaces of value names.
 * Implemented under influence of ZendFramework ZendConfig class.
 * 
 * Example of inherited sections:
 * <pre><code>
 * [base]
 * db = "mysqli://root:@localhost/cassea"
 * x_ua_compatible= "off"
 * [config:base]
 * db="mysqli://root:root@localhost/test"
 * </code></pre>
 * 
 * In "base" section defines default value for "db" and "x_ua_compatible". 
 * Config section "config" inherits all values from
 * "base" section (using ":" means inheritance). As result it has "db" and "x_ua_compatible" properties.
 * But it also overrides "db" to set actual information for DB connection. 
 *
 * Pay attention that no multiple inheritance allowed.
 *
 * Also there are several conventions:
 * <ul>
 * <li>Config file placed in <root_dir>/config directory and has name 
 * <code>config.ini</code>. To define other filename, use <code>CONFIG</code> 
 * constant before {@link Config} initialization. For example,
 * <pre><code>
 * define("CONFIG","new_config.ini");
 * </code></pre>
 * In this case, file <root_dir>/new_config.ini will be used.
 * </li>
 * <li>Base section has name "base" and all default values are placed here.</li>
 * <li>Top hierarchy section has name "config". Values in this section overrides all other values.
 * To change section, use <code>CONFIG_SECTION</code> before {@Config} initialization. For example,
 * <pre><code>
 * define("CONFIG_SECTION","new_section");
 * </code></pre>
 * In this case, "new_section" will be at the top of hierarchy.
 * </li>
 * </ul>
 *
 * In order to have namespaces on config properties, use "." in property name. E.g.
 * <pre><code>
 * mail.transport = "smtp"
 * mail.default_from = "cassea@cassea"
 * </pre></code>
 * This usefull, if you have various config properties with the name "transport". Particular property 
 * is related to mail subsytem.
 */
class IniConfig extends ConfigBase
{
	const CONFIG_DIR = "/config";
	const CONFIG_CACHE_FILE = "/cache/config.cache";

	protected
		/**
		 * @var string name of parsed file
		 */
		$filename = null,
		/**
		 * @var string name of current section
		 */
		$section = null,
		/**
		 * @var string symbol used as inheritance separator. Default is ":".
		 */
		$inherit_separator = ":",
		/**
		 * @var string cached path for root dir
		 */
		$rd = ""
		;

	//{{{ __construct
	/**
	 * Parses given file and section, fill inner data structure by described rules.
	 *
	 * @param string name of the file. Usually "config.ini", passed from {@link Boot}.
	 * @param string name of the main section. Usually "config", passed from {@link Boot}
	 * @param string symbol that used as inherit separator. ":" by default.
	 * @throws ConfigException with error message
	 */
	function __construct($filename, $section = null,  $inherit_separator = ":")
    {
        $_r = (!empty($_SERVER['DOCUMENT_ROOT']) && is_readable($_SERVER['DOCUMENT_ROOT']))?$_SERVER['DOCUMENT_ROOT']:dirname(dirname(__FILE__));
		if(empty($filename) || !file_exists($_r) || !file_exists($this->filename = $_r.self::CONFIG_DIR."/".$filename))
			throw new ConfigException("Config file ".$this->filename." doesn't exists");

		if(!isset($inherit_separator))
			throw new ConfigException("Inherit separator doesn't exists");

		if(!isset($section))
			throw new ConfigException("Section ".$section." doesn't exists");

		$this->rd = $_r;

		$this->section = $section;
		$this->inherit_separator = (string)$inherit_separator;

		if($this->checkCache())
		{
			include($this->rd.self::CONFIG_CACHE_FILE);
			$this->parseArray($__config_cache);
			return;
		}
		$parsed_ini = parse_ini_file($this->filename,true);

		list($sect_key,$sect_val) = $this->findSection($parsed_ini,$this->section);
		if(!isset($sect_key, $sect_val)) return;

		$config_values = array();

		// init with values from main section
		$arr_to_parent = array();
		foreach($sect_val as $ik => $iv)
			$arr_to_parent = array_merge_recursive($arr_to_parent,$this->processKeys(explode(".",$ik),$iv));

		array_push($config_values,$arr_to_parent);

		unset($arr_to_parent);

		// init with values in sections up by inheritance list
		while(count(($expl = explode($this->inherit_separator,$sect_key))) == 2) 
		{
			list($inh_key,$inh_val) = $this->findSection($parsed_ini,
				trim($expl[1]));
			if(isset($inh_key,$inh_val))
			{
				$arr_to_parent = array();
				foreach($inh_val as $ik => $iv)
					$arr_to_parent = array_merge_recursive($arr_to_parent,$this->processKeys(explode(".",$ik),$iv));
				array_push($config_values,$arr_to_parent);
				unset($arr_to_parent);
			}
			else break;

			$sect_key = $inh_key;
		}

		//convert to hierarchy
		foreach(array_reverse($config_values) as $v)
			$this->merge(new ConfigBase($v));
	}
	//}}}

	//{{{ findSection
	/**
	 * Finds section with given name in passed array of parsed ini file. 
	 * Mainly for internal use.
	 *
	 * @param array returned from parse_ini_file
	 * @param string name of the section to find
	 * @retrun array of 2 elements: finded section name (i.e. "config:base") and section content. 
	 * Or array(nul,null) if nothing was founded.
	 */
	protected final function findSection(array $arr,$sect_name)
	{
		foreach(array_keys($arr) as $key)
			if(($kp = explode($this->inherit_separator,$key)) && trim($kp[0]) == $sect_name)
				return array($key,$arr[$key]);
		return array(null,null);
	}
	//}}}
	
	//{{{ processKeys
	/**
	 * Makes from array from key, exploded by "." and value hierarchical structure.
	 * I.e. 
	 * <pre><code>
	 * from
	 *
	 * Array
	 *	(
	 *		[0] => mail
	 *		[1] => default_from
	 *	)
	 * 
	 * as first parameter and "robot@example.com" as second parameter makes
	 *
	 *
	 *	Array
	 *	(
	 *		[mail] => Array
	 *			(
	 *				[default_from] => robot@example.com
	 *			)
	 *	)
	 * </code></pre>
	 *
	 * Mainly for internal use.
	 *
	 * @param array exploded key
	 * @param string config value 
	 * @return array of described format
	 */
	protected final function processKeys($arr,$value)
	{
		if(empty($arr)) return $value;
		$el = array_pop($arr);
		return $this->processKeys($arr,array($el=>$value));
	}
	//}}}
	
	//{{{ getSectionName
	/**
	 * Returns main section name
	 *
	 * @param null
	 * @return string
	 */
    function getSectionName()
    {
        return $this->section;
    }
	//}}}
	
	//{{{
	/**
	 * It cheks if config.cache file is valid to be used.
	 * This checks are based on the modtification times and
	 * estimating cache file size.
	 *
	 * @param null
	 * @return bool true if cache is in actual state
	 */
	function checkCache()
	{
		$synced = false;
		if(($fp = fopen($this->filename, 'r')) === false) return false;
		if(flock($fp, LOCK_SH))
		{
			$config_stat = stat($this->filename);
			$config_cache_stat = @stat($this->rd.self::CONFIG_CACHE_FILE);
			if(!empty($config_stat) && !empty($config_cache_stat) 
				&& $config_stat['mtime'] == $config_cache_stat['mtime'] 
				&& $config_cache_stat['size'] > 30)
				$synced = true;
			flock($fp,LOCK_UN);
		}
		fclose($fp);

		return $synced;

	}
	//}}}
	
	//{{{ __destruct
	/**
	 * Here in destructor sync cache with actual config values will be
	 * started.
	 */
	function __destruct()
	{
		if(!$this->checkCache())
		{
			if(($fp = fopen($this->rd.self::CONFIG_CACHE_FILE,"a")) === false) return;
			if(flock($fp,LOCK_EX))
			{
				ftruncate($fp,0);
				fputs($fp,'<?php'.PHP_EOL.'$__config_cache = '.var_export($this->toArray(),true).';');
				$time = time();
				touch($this->rd.self::CONFIG_CACHE_FILE,$time);
				touch($this->filename,$time);
				fflush($fp);
				flock($fp,LOCK_UN);
			}
			fclose($fp);
		}
	}
	//}}}
}
//}}}

//{{{ IniDBConfig
/**
 * This class adds to IniConfig class capability to store changable data in DB via {@link set} method.
 * It uses lazy load initialization, so if you don't use any config values from DB in current view, no SQL
 * queries will be maid.
 *
 * Only plain config valus may be stored in DB. Namespaces are not supported.
 */
class IniDBConfig extends IniConfig
{
	/**
	 * @var array config data from DB
	 */
	private $table_data = null;
	/**
	 * Table name to store config values
	 */
    const TABLE_NAME = 'config';

	//{{{ loadData
	/**
	 * Initialize config values from DB
	 *
	 * @param null
	 * @retrun null
	 */
    protected function loadData()
    {
        $this->table_data = array();
        try
        {
        foreach(DB::query("select * from ".self::TABLE_NAME) as $v)
            $this->table_data[strtolower($v['key'])] = $v['value'];
        }catch(DBConnectException $e) {}
	}
	//}}}
	
	//{{{ get
	/**
	 * Retrieves variable or ConfigBase object by name. 
	 * This method is called internally by Config class-facade.
	 *
	 * @param string  config value name
	 * @return mixed config value. It can be either string or ConfigBase instance.
	 * @throws ConfigException if given config values doesn't exists
	 * @see Config
	 * @see Config::getInstance
	 * @see Config::get
	 * @throws ConfigException if no value was found
	 */
    function get($name)
    {
        try{
            return  parent::get($name);
        }catch(ConfigException $e){
            if(!isset($this->table_data))
				try {
					$this->loadData();
				}catch(DBException $e) {}
            $name = strtolower($name);
            if(isset($this->table_data[$name]))
                return $this->table_data[$name];
            else
				throw new ConfigException("Config value ".$name." doesn't exists");
        }
	}
	//}}}
	
	//{{{ __get
	/**
	 * Magick method to retrieve variable or ConfigBase object by name.
	 * It can be used as convinient alternative to method {@link get}.
	 *
	 * @param string config value name
	 * @return mixed config value. It can be either string or ConfigBase instance
	 * @throws ConfigException if given config values doesn't exists
	 */
    function __get($name)
    {
        return $this->get($name);
    }
	//}}}

	//{{{ set
	/**
	 * Store config value. If this config value name presents in DB, it will be updated in it. Else
	 * it stores only in memory for particular request and value will be destroyed when script ends.
	 *
	 * @param string name of the config value
	 * @param mixed value to store in config. It can be either scalar or array or ConfigBase instance.
	 * Scalar and array value will be converted using {@link ConfigBase::parseArray}, ConfigBase instances will be 
	 * assigned directly.
	 * @return null
	 */
    function set($name, $value)
    {
		if(!isset($this->table_data))
			$this->loadData();
        if(isset($this->table_data[$name],$name,$value))
        {
            $name = Filter::apply($name,'STRING_QUOTE');
            $value = Filter::apply($value,'STRING_QUOTE');
            $this->table_data[$name] = $value;
            DB::query("update ".self::TABLE_NAME." set value='".$value."' where `key`='".$name."' limit 1");
        }
        //else $this->parseArray(array($name=>$value));
        else $this->$name = $value;
    }
	//}}}

	//{{{ __isset ===DEPRECATED===
	/**
	 * @deprecated
    function __isset($name)
    {
        return isset($this->table_data[$name]) || parent::__isset($name);
	}
	 */
	//}}}

	//{{{ __unset
	/**
	 * Unsets config value or whole namespace if exists. 
	 * If config value presents in DB it deletes from DB.
	 * Otherwise behaviour would be similar to {@link ConfigBase::__unset}.
	 *
	 * @param string config value name or namespace name
	 * @return null
	 */
    function __unset($name)
    {
		if(!isset($table_data))
			$this->loadData();
		if(isset($this->table_data[$name]))
            DB::query("delete from ".self::TABLE_NAME." where `key`='".Filter::apply($name,'STRING_QUOTE')."' limit 1");
        parent::__unset($name);
	}
	//}}}
}

//{{{ Config
/**
 * It acts as facade with static methods to give easy access to config values.
 * All request to config values passes through it.
 */
class Config
{
	/**
	 * Instance of self. For singleton behaviour
	 */
	private static $instance = null;
	//{{{ init
	/**
	 * Init singleton. It's one of the topmost initializations, made by {@Boot} class.
	 *
	 * @param ConfigBase instance of config class
	 * @retrun null
	 */
	static function init(ConfigBase $config)
	{
		if(!isset(self::$instance))
			self::$instance = $config;
	}
	//}}}
	
	//{{{ get
	/**
	 * Works to retrieve plain values (i.e. without namespaces such as "db", "config_table", etc).
	 *
	 * Example of using
	 * <pre><code>
	 * $db_dsn = Config::get("db");
	 * </code></pre>
	 *
	 * @param string config value name to retrieve
	 * @retrun string vaue itself
	 * @throws ConfigException if trying to retreive value with non plain name.
	 */
	static function get($parameter)
	{
		if(self::$instance === null) throw new ConfigException("Config hasn't been initialized");
		if(self::$instance->$parameter instanceof ConfigBase)
			throw new ConfigException("Only plain values could be retrieved. Use fluent interfaces instead");
		return self::$instance->$parameter;
	}
	//}}}

	//{{{ getInstance
	/*
	 * Returns instance of used config object.
	 *
	 * This method could be used in fluent interfaces if using namespaces, e.g. for mail namespace
	 * <pre><code>
	 * $transport = Config::getInstance()->mail->transport;
	 * </code></pre>
	 * <pre><code>
	 * $db_dsn = Config::getInstance()->db;
	 * </code></pre>
	 *
	 * @param null
	 * @return ConfigBase instance of using config object
	 */
	static function getInstance()
	{
		if(self::$instance === null) throw new ConfigException("Config hasn't been initialized");
		return self::$instance;
	}
	//}}}
}
