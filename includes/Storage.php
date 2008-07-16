<?php
interface StorageEngine
{
	function set($var, $val);
	function get($var);
	function is_set($var);
	function un_set($var);
	function sync();
}
class StorageException
{
	function __construct($message)
	{
		parent::__construct($message);
	}
}
class FSStorage implements StorageEngine
{
	const STORAGE_PATH = "/cache/storage";
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
		$this->real_storage_path = Config::get('ROOT_DIR').self::STORAGE_PATH."/".md5($storage_name);
		
		if(!is_dir($this->real_storage_path))
			mkdir($this->real_storage_path);

		if(!is_dir($this->real_storage_path))
			throw(new StorageException('storage dir is empty'));
		
		if (!isset($ttl)) $ttl = 86400; //1day
		$this->ttl = (int)$ttl;

		file_put_contents($this->real_storage_path."/.ttl",time()+$this->ttl);

		foreach(glob($this->real_storage_path."/*.cache") as $f)
			$this->vars[basename($f,".cache")] = unserialize($f);
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
		unlink($this->real_storage_path."/".$m.".cache");
	}
	function get($var)
	{
		if(isset($this->vars[$var]))
			return $this->vars[$var];
		return false;
	}
	function sync()
	{
		foreach(glob($this->real_storage_path."/*.cache") as $f)
			$this->vars[basename($f,".cache")] = unserialize($f);
	}
	static function cleanup()
	{
		$dir = Config::get('ROOT_DIR').self::STORAGE_PATH;
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
		$this->memcache->pconnect('localhost',11211);
	}
	
	function is_set($var)
	{
		$f = $this->memcache->get(md5($this->storage_name.$var));
		if($f === false) return false;
		return true;
	}
	function set($var,$val)
	{
		if($this->is_set($var))
			$r = $this->memcache->replace(md5($this->storage_name.$var),$val,false,$this->ttl);
		else
			$r = $this->memcache->set(md5($this->storage_name.$var),$val,false,$this->ttl);
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
}
?>
