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
 * @package Storage
 */

// {{{ FilesystemStorage
/**
 *
 */
class FilesystemStorage extends AbstractStorage
{

	private	$vars = array(),
			$sync = array(),
			$dir;
	//
    //{{{ __construct
	function __construct($storageName, $ttl = null, $withSession=false){
		parent::__construct($storageName, $ttl, $withSession);
		if ($withSession) $this->name .= Session::getInstance()->getId();
		$this->name = md5($this->name);
		$this->dir = self::getDir($this->name);
	}// }}}

	// {{{ getDir
	/**
	 *
	 */
	static function getDir($storageName){
		static $dir = null;
		if (is_null($dir)){ 
			$dir = Dir::get( Config::get('ROOT_DIR').Config::get('STORAGE_DIR'), true );
			TTL::init();
		}
		return $dir->getDir('data/'.$storageName);
	}// }}}

	// {{{ destroy
	static function destroy($storageName){
		self::getDir($storageName)->delete();
	}// }}}

	static private function getKeyName($key){
		return md5($key);
	}

	private function getKeyFile($key){
		return $this->dir->getFile((self::getKeyName($key)));
	}

	// {{{is_set
	function is_set($key)
	{
		$k = self::getKeyName($key);
		if(isset($this->vars[$k])) return true;
		if(!$this->getKeyFile($key)->exists()) return false;
		try{
			$this->vars[$k]=unserialize($this->getKeyFile($key)->content);
			return true;
		}
		// file with var  or storage dir not exists
		catch(FileSystemException $e){}
		return false;
    }// }}}

    // {{{ set
	function set($key,$val)
	{
		$key = self::getKeyName($key);
		if (isset($this->vars[$key]) && $this->vars[$key] === $val) return true;
		$this->vars[$key] = $val;
		$this->sync[] = $key;
		return true;
    }// }}}

    // {{{ un_set
	function un_set($key)
	{
		$this->getKeyFile($key)->delete();
		unset($this->vars[self::getKeyName($key)]);
	}
	//}}}
	
	//{{{get
	function get($key)
	{
		if($this->is_set($key))
			return $this->vars[self::getKeyName($key)];
		return false;
    }// }}}

    // {{{ sync
    function sync()
	{
		if (!count($this->sync)) return;
		try{$this->syncData();}
		catch(FileSystemException $e){
			$this->dir->mkdir();
			if (!$this->dir->canWrite()) throw $e;
			$this->syncData();
		}
		$this->sync = array(); // for multiple sync
	}
	// }}}

	private function syncData(){
		foreach($this->sync as $key)
			$this->dir->getFile($key)->content = serialize($this->vars[$key]);
	}

    // {{{ cleanup 
	function cleanup()
	{
		TTL::updateGroup();
		TTL::cleanup();
    }// }}}

	//{{{__destruct
	public function __destruct(){
		$this->sync();
		if($this->dir->exists()) TTL::queued($this->name, $this->ttl);
		parent::__destruct();
	}	
	//}}}
	
}// }}}
