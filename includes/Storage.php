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

interface StorageEngine
{
	function set($var, $val);
	function get($var);
	function is_set($var);
	function un_set($var);
	function sync();
}
class StorageException extends Exception
{
	function __construct($message)
	{
		parent::__construct($message);
	}
}
class FSStorage implements StorageEngine
{
	private $storage_name = null,
			$vars = array(),
			$ttl = null,
			$real_storage_path = null
			;
	function __construct($storage_name, $ttl = null)
	{
		self::cleanup();
		if(empty($storage_name))
			throw(new StorageException('storage name is empty'));

        $this->storage_name = $storage_name;
        
        if(!is_dir(Config::get('ROOT_DIR').Config::get('STORAGE_PATH')))
            mkdir(Config::get('ROOT_DIR').Config::get('STORAGE_PATH'));

		$this->real_storage_path = Config::get('ROOT_DIR').Config::get('STORAGE_PATH')."/".md5($storage_name);
		
		if(!is_dir($this->real_storage_path))
			mkdir($this->real_storage_path);

		if(!is_dir($this->real_storage_path))
			throw(new StorageException('storage dir is empty'));
		
		if (!isset($ttl)) $ttl = 86400; //1day
		$this->ttl = (int)$ttl;

		file_put_contents($this->real_storage_path."/.ttl",time()+$this->ttl);

        foreach(glob($this->real_storage_path."/*.cache") as $f)
        {
            $this->vars[basename($f,".cache")] = file_get_contents($f);
        }
	}
	function is_set($var)
	{
		return isset($this->vars[md5($var)]);
	}
	function set($var,$val)
	{
		$m = md5($var);
		$this->vars[$m] = $val;
		$r = file_put_contents($this->real_storage_path."/".$m.".cache",serialize($val));
		if($r === false) return false;
		return true;
	}
	function un_set($var)
	{
		$m = md5($var);
		if(isset($this->vars[$m]))
			unset($this->vars[$m]);
        if(file_exists($this->real_storage_path."/".$m.".cache"))
		    unlink($this->real_storage_path."/".$m.".cache");
	}
	function get($var)
    {
        $var = md5($var);
		if(isset($this->vars[$var]))
			return unserialize($this->vars[$var]);
		return false;
	}
	function sync()
	{
		foreach(glob($this->real_storage_path."/*.cache") as $f)
			$this->vars[basename($f,".cache")] = unserialize($f);
	}
	static function cleanup()
	{
		$dir = Config::get('ROOT_DIR').Config::get('STORAGE_PATH');
		foreach(glob($dir."/*/*.ttl") as $f)
			if(intval(file_get_contents($f)) < time())
				deltree(dirname($f));
	}
}
class MemcacheStorage
{
	private $storage_name = null,
			$ttl = null,
			$memcache = null
			;
	function __construct($storage_name, $ttl = null)
	{
		if(empty($storage_name))
			throw(new StorageException('storage name is empty'));

		if(!extension_loaded('memcache'))
			throw(new StorageException('extension does not loaded'));

		$this->storage_name = $storage_name;
		
		if (!isset($ttl)) $ttl = 86400; //1day
		$this->ttl = (int)$ttl;

		$this->memcache = new Memcache;
        if($this->memcache->pconnect(Config::get('MEMCACHED_HOST'),Config::get('MEMCACHED_PORT')) === false)
            throw new StorageException('could not connect to server');
	}
	
	function is_set($var)
    {
        // @ used due to strage warnings
		@$f = $this->memcache->get(md5($this->storage_name.$var));
		if($f === false) return false;
		return true;
	}
	function set($var,$val)
    {
        // @ used due to strage warnings
		if($this->is_set($var))
			@$r = $this->memcache->replace(md5($this->storage_name.$var),$val,false,$this->ttl);
		else
			@$r = $this->memcache->set(md5($this->storage_name.$var),$val,false,$this->ttl);
		return $r;
	}
	function get($var)
	{
		return $this->memcache->get(md5($this->storage_name.$var));
	}
	function un_set($var)
	{
		$this->memcache->delete(md5($this->storage_name.$var));
	}
	function sync(){}
	function __destruct()
	{
		$this->memcache->close();
	}
}
class Storage 
{
	static function create($storage_name,$ttl = null)
	{
		if(Config::get('STORAGE_ENGINE') == "memcache")
			return new MemcacheStorage($storage_name,$ttl);
		else 
			return new FSStorage($storage_name,$ttl);
	}
	static function createWithSession($storage_name,$ttl = null)
	{
		return self::create($storage_name.Session::getInstance()->getId(),$ttl);
	}
}
?>
