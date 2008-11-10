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

class ConfigException extends Exception {}
interface SaveableConfig
{
	function save();
}
class ConfigBase implements IteratorAggregate
{
	const CONFIG_DIR = "/config";

    protected $allow_modifications = true,
			 $data = array(),
			 $section = null,
			 $extends = array()
			 ;

    function __construct(array $array = array(), $allow_modifications = true)
    {
        $this->allow_modifications = $allow_modifications;
		$this->data['root_dir'] = dirname(dirname(__FILE__));
		$this->parseArray($array);
	}
	protected final function parseArray(array $array = array())
	{
        foreach ($array as $key => $value) 
            if (is_array($value)) 
                $this->data[$key] = new self($value, $this->allow_modifications);
             else 
                $this->data[$key] = $value;
	}
    function get($name)
    {
		$name = strtolower($name);
        if(isset($this->data[$name]))
            return $this->data[$name];
        else
            throw new ConfigException("Config value ".$name." doesn't exists");
    }
    function __get($name)
    {
        return $this->get($name);
    }

    function __set($name, $value)
    {
		if (!$this->allow_modifications) 
			throw new ConfigException("Config is read only");
		
		$this->parseArray(array($name=>$value));
    }

    function __isset($name)
    {
        return isset($this->data[$name]);
    }

    function __unset($name)
    {
		if (!$this->allow_modifications) 
			throw new ConfigException("Config is read only");

		if(isset($this->data[$name]))
			unset($this->data[$name]);
    }
	
    function setReadOnly()
    {
        $this->allow_modifications = false;
    }

    function toArray()
    {
        $array = array();
        foreach ($this->data as $key => $value)
            if ($value instanceof ConfigBase) 
                $array[$key] = $value->toArray();
             else 
                $array[$key] = $value;

        return $array;
    }
	function getIterator()
	{
		return t(new ArrayObject($this->data))->getIterator();
	}

    function getSectionName()
    {
        return $this->section;
    }

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
}
class IniConfig extends ConfigBase implements SaveableConfig
{
	protected
		$filename = null,
		$inherit_separator = ":";

	function __construct($filename, $section = null, $allow_modifications = true, $inherit_separator = ":")
	{
		if(empty($filename) || !file_exists($_SERVER['DOCUMENT_ROOT']) || !file_exists($this->filename = $_SERVER['DOCUMENT_ROOT'].self::CONFIG_DIR."/".$filename))
			throw new ConfigException("Config file ".$this->filename." doesn't exists");

		$this->section = $section;
		$this->allow_modifications = (bool)$this->allow_modifications;
		$this->inherit_separator = (string)$inherit_separator;

		$parsed_ini = parse_ini_file($this->filename,true);
		list($sect_key,$sect_val) = $this->findSection($parsed_ini,$this->section);
		if(!isset($sect_key, $sect_val)) return;


		if(count(($expl = explode($this->inherit_separator,$sect_key))) == 2) 
		{
			list($inh_key,$inh_val) = $this->findSection($parsed_ini,
				trim($expl[1]));
			if(isset($inh_key,$inh_val))
			{
				$arr_to_parent = array();
				foreach($inh_val as $ik => $iv)
					$arr_to_parent = array_merge_recursive($arr_to_parent,$this->processKeys(explode(".",$ik),$iv));
				$this->merge(new ConfigBase($arr_to_parent));
			}
		}
		elseif(count($expl) > 2)
			throw new ConfigException("Multiple inheritance not allowed");

		$arr_to_parent = array();
		foreach($sect_val as $vk => $vv)
			$arr_to_parent = array_merge_recursive($arr_to_parent,$this->processKeys(explode(".",$vk),$vv));
        //parent::__construct($arr_to_parent);
        $this->merge(new configBase($arr_to_parent));

		
	}
	protected final function findSection($arr,$sect_name)
	{
		foreach(array_keys($arr) as $key)
			if(($kp = explode($this->inherit_separator,$key)) && trim($kp[0]) == $sect_name)
				return array($key,$arr[$key]);
		return array(null,null);
	}
	protected final function processKeys($arr,$value)
	{
		if(empty($arr)) return $value;
		$el = array_pop($arr);
		return $this->processKeys($arr,array($el=>$value));
	}
	function save()
	{
		if(!$this->allow_modifications) 
			throw new ConfigException("Config is read only");
		throw new ConfigException("Not implemented yet :)");
	}
}
class Config
{
	private static $instance = null;
	static function init(ConfigBase $config)
	{
		if(!isset(self::$instance))
			self::$instance = $config;
	}
	static function get($parameter)
	{
		return self::$instance->$parameter;
	}
	static function getInstance()
	{
		return self::$instance;
	}
}

/*$config = new IniConfig("config.ini","intvideo");
print_pre($config);*/
?>
