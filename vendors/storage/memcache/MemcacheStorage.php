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

class MemcacheException extends StorageException{}

// {{{ MemcacheCnnection
/**
 * Класс осуществляет соединнение с Memcache сервером(серверами) 
 * и обрабатывает ошибки (server down)
 *
 * Для всех обращений к Memcache ипользуется один объект(экономятся соединения).
 *
 * Срока memcache.servers в файле config.ini содержит список серверов(разделенных запятыми) и опционально, 
 * раметры подключения для каждого сервера.
 *
 * В общем виде параметры каждого сервера описываются ввиде URI:
 *		tcp://host:port[?param1=value1&...]
 * где host:port - хост и порт memcache сервер, являются обязательными,
 * а список параметров может состоять из
 *  - persistent	использовать постоянное соединение с сервером. Значения: 0, 1. По умолчанию 0;
 *  - weight		вес(приоритет) сервера в пуле. По умолчанию 1. Значение: положительный int;
 *  - timeout		
 *  - retry_interval через сколько секунд повторять попытку соединения с сервером после неудачной(по timeout'у) попытки; 
 *  - status		 делать проверку о рабочем состоянии сервера. По умолчанию TRUE;
 *  - failure_callback Функция вызываемя при падении сервера; По умолчанию используется: MemcacheConnection::failure. 
 *
 * Например(config.ini):
 * memcache.servers =	"tcp://localhost:11211?persistent=1&weight=10&time_out=15,
 *		                 tcp://192.168.0.4:11211?persistent=1&weight=10&time_out=15"
 *
 * В случае отказа сервера(при использовании  failure_callback = MemcacheConnection::failure):
 *   - для конфигурации из одного сервера выбрасывается исключение;
 *   - для пула серверов: если в пуле остались сервера, то ключи, размещенные на упавшем сервере теряются, 
 *   но при этом ключи на остальных серверах остаются целыми;
 *   Если в пуле больше нет серверов выбрсывается исключение.
 *
 * После того как исключение сигнализирующее о том, что нет доступных серверов выброшено,
 * система не генерирует ошибки или исключения. Устанавливаемые ключи никуда не сохраняются(set() функция возвращет false)
 * и все запросы на получение ключей возвращают false; 
 *
 */
class MemcacheConnection{
	private static	$defaultParams = array(
		'persistent' => false,
		'weight' => 1,
		'timeout' => 1,
		'retry_interval' => 15,
		'status' => true,
		'failure_callback' => 'MemcacheConnection::failure'
		);
	private static $memcache = null;
	private static $servers = null;
	
	// {{{ init 
	static private function init(){
		if (is_null(self::$servers)) self::parseConfig();
		self::$memcache = new Memcache();
		if (count(self::$servers) == 1) self::connect();
		else self::connectPool();
	}// }}}

	// {{{ parseConfig
	static private function parseConfig(){
		// get servers
		$servers =  explode(',',Config::getInstance()->memcache->servers);
		array_walk($servers, create_function('&$k, $i', '  $k = trim($k);'));
		if (!count($servers)) throw new StorageException('There is no memcache servers set in config');
		foreach ($servers as $server ){
			$s = parse_url($server);
			if (isset($s['host'], $s['port'])){
				if(isset($s['query']))
					parse_str($s['query'], $param);
				self::$servers[]  = array(
					'host' => $s['host'],
					'port' => $s['port'],
					'param' => isset($param)?$param:null
				);
			}
		}
	}// }}}

	// {{{ setParams
	static private function setParams($server){
		if (!isset($server['param']) || !is_array($server['param'])) return;
		$param = $server['param'];
		foreach (self::$defaultParams as $k => $v)
			$param[$k] = isset($param[$k])?$param[$k]:$v;
		self::$memcache->setServerParams( $server['host'], $server['port'], $param['timeout'], $param['retry_interval'], $param['status'], $param['failure_callback']);
	}// }}}
	
	// {{{ connect
	static private function connect(){
		$s = array_pop(self::$servers);
		if (isset($s['param']) && isset($s['param']['persistent'] ) && $s['param']['persistent'] == 1 ) $res = @self::$memcache->pconnect($s['host'],$s['port']);
		else  $res = @self::$memcache->connect($s['host'],$s['port']);
		if (!$res) throw new MemcacheException ('Can\'t connect to Memcache Server('.$s['host'].':'. $s['port'].')');
		self::setParams($s);
	}//}}}

	// {{{ connectPool
	static private function connectPool(){
		foreach(self::$servers as $server){
			$r = self::$memcache->addServer( $server['host'], $server['port']);//, $param['persistent'], $param['weight'], $param['timeout'], $param['retry_interval'], $param['status'], $param['failure_callback']);
			self::setParams($server);
		}
	}// }}}

	// {{{ getMemcacheObj
	/**
	 *
	 * @return Memcache
	 */
	static public function getMemcacheObj(){
		if (!is_object(self::$memcache)) self::init();
		return self::$memcache;
	}// }}}
	
	// {{{ failure
	/**
	 * функция вызываемая memcache в случае если сервер не смог обработать запрос(set, get, etc) или недоступен.
	 *
	 * @throws StorageException
	 * @param 
	 */
	static public function failure( ){
		$a = func_get_args();
		for($i =0, $c = count(self::$servers); $i < $c; $i++){
			echo "try ".$i;
			if (self::$servers[$i]['host'] == $a[0] && self::$servers[$i]['port'] == $a[1] ) {unset(self::$servers[$i]) ; break;}
		}
		$err = sprintf('Memcache server %s:%s fail. Reason: %s',$a[0], $a[1], isset($a[3])?$a[3]:'Unknown');
		trigger_error($err, E_USER_NOTICE);

		self::$servers = array_values(self::$servers);
		if (count(self::$servers) == 0) throw new StorageException($err);  
		else self::$memcache->setServerParams($a[0],$a[1],1,-1, false); 

	}// }}}
}// }}}



// {{{ MemcacheStorage
/**
 *
 */
class MemcacheStorage implements StorageEngine, ArrayAccess
{
	private $storage_name = null,
            $ttl = null,
			$memcache = null;
    // {{{ __construct
	function __construct($storage_name, $ttl = null)
	{
		if(empty($storage_name))
			throw(new StorageException('storage name is empty'));

		if(!extension_loaded('memcache'))
			throw(new StorageException('extension does not loaded'));

		$this->storage_name = $storage_name;
		
        if (!isset($ttl)) $ttl = Config::getInstance()->session->length;
        $this->ttl = min((int)$ttl, 2592000 );

		$this->memcache = MemcacheConnection::getMemcacheObj();
	}// }}}


    // {{{ is_set
	function is_set($var)
    {
		$f = $this->memcache->get(md5($this->storage_name.$var));
		if($f === false) return false;
		return true;
	}// }}}

    // {{{ set
    function set($var,$val)
    {
        return  $this->memcache->set(md5($this->storage_name.$var),$val,false,$this->ttl);
    }// }}} 

     // {{{ get
	function get($var)
	{
		return $this->memcache->get(md5($this->storage_name.$var));
    }// }}}

    // {{{ un_set
	function un_set($var)
    {
		$this->memcache->delete(md5($this->storage_name.$var));
    }// }}} 

    // {{{ sync
    function sync(){}
    // }}} sync

    // {{{ __destruct
	function __destruct()
	{
		//$this->memcache->close();
    }
    // }}}
    
    // {{{ close
	function close()
	{
		$this->memcache->close();
    }
    // }}}

    //  {{{ ArrayAccess interface
    public function offsetExists($key){ return $this->is_set($key);}
    public function offsetGet($key){ return $this->get($key);}
    public function offsetSet($key, $val){ return $this->set($key, $val);}
    public function offsetUnset($key){ return $this->un_set($key);}
    // }}}

}// }}}