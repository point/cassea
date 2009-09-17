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
 *
 *
 *
 * @package Storage
 */

// {{{ FSStorage
/**
 *
 */
class FilesystemStorage implements StorageEngine, ArrayAccess
{
	private $storage_name = null,
			$vars = array(),
			$ttl = null,
			$real_storage_path = null
            ;
    // {{{ __construct
	function __construct($storage_name, $ttl = null)
	{
		self::cleanup();
		if(empty($storage_name))
			throw(new StorageException('storage name is empty'));
        $this->storage_name = $storage_name;
        
        if(!is_dir(Config::get('ROOT_DIR').Config::get('STORAGE_DIR')))
            mkdir(Config::get('ROOT_DIR').Config::get('STORAGE_DIR'));

		$this->real_storage_path = Config::get('ROOT_DIR').Config::get('STORAGE_DIR')."/".md5($storage_name);
		
		if(!is_dir($this->real_storage_path))
			mkdir($this->real_storage_path);

		if(!is_dir($this->real_storage_path))
			throw(new StorageException('storage dir is empty'));
		
        if (!isset($ttl)) $ttl = Config::getInstance()->session->length;
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
    }// }}}

    // {{{ set
	function set($var,$val)
	{
		$m = md5($var);
		$val = serialize($val);	
		$this->vars[$m] = $val;
		$r = file_put_contents($this->real_storage_path."/".$m.".cache", $val);
		if($r === false) return false;
		return true;
    }// }}}

    // {{{ un_set
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
    }// }}}

    // {{{ sync
    function sync()
	{
		foreach(glob($this->real_storage_path."/*.cache") as $f)
			$this->vars[basename($f,".cache")] = unserialize($f);
    }// }}}

    // {{{ cleanup 
	static function cleanup()
	{
		$dir = Config::get('ROOT_DIR').Config::get('STORAGE_DIR');
		foreach(glob($dir."/*/*.ttl") as $f)
			if(intval(file_get_contents($f)) < time())
				deltree(dirname($f));
    }// }}}

    // {{{ close
	function close()
	{
    }
    // }}}
    //  {{{ ArrayAccess interface
    public function offsetExists($key){ return $this->is_set($key);}
    public function offsetGet($key){ return $this->get($key);}
    public function offsetSet($key, $val){ return $this->set($key, $val);}
    public function offsetUnset($key){ return $this->un_set($key);}
    // }}}

}// }}}

