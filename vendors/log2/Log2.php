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
 * This file contains class Log.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

//{{{ Log2
/**
 */
class Log2
{

	private static $path = null;

	private static $path_instances = array();

	private static $format_strings = array();

    /**
     * @var object Log object or null. Using in static method get
     */
    private static $instance = null;

    //{{{ f
    /**
     */
	public static function f($name = 'all') 
	{
		if(empty($name)) 
			throw new Log2Exception("Incorrect name '$name' for 'for' method");
		self::$path = $name;
		if(!isset(self::$instance))
			self::$instance = new Log2();
	}
	//}}}
	

	// ->findLogConfigValue(self::$path,"priority");
	private function findLogConfigValue($part1,$part2,$only_w_part2 = true, $failback_to_all = true)
	{
		$config = Config::getInstance()->log2;

		$to_parse = "";

		$found_quick = 
			$found = 
			$found_failback = 
			$found_w_part2 = false;

		try{
			$to_parse = $config->{$part1.":".$part2};
			$found_quick = true;
			$found_w_part2 = true;
		}
		catch(ConfigException $e){
			try{
				if(!$only_w_part2)
				{
					$to_parse = $config->$part1;
					$found_quick = true;
				}
			}
			catch(ConfigException $e){}
		}

		if(!$found_quick)
			if(strpos($part1,".") !== false)
				while(!$found || ($part1 = substr($part1,0,strrpos(self::$path,"."))) !== false)
					try{
						$to_parse = $config->{$part1.":".$part2};
						$found = true;
						$found_w_part2 = true;
					}
					catch(ConfigException $e){
						try{
							if(!$only_w_part2)
							{
								$to_parse = $config->$part1;
								$found = true;
							}
						}
						catch(ConfigException $e){}
					}

		if(!$found_quick && !$found && $failback_to_all)
			try{
				$to_parse = $config->{"all:".$part2};
				$found_failback = true;
				$found_w_part2 = true;
			}
			catch(ConfigException $e){
				try{
					if(!$only_w_part2)
					{
						$to_parse = $config->all;
						$found_failback = true;
					}
				}
				catch(ConfigException $e){}
			}
		if(!$found_quick && !$found && !$found_failback)
			throw new Log2NotFoundException("Section '$part1' wasn't found in config");
		
		return array($to_parse,$found_w_part2,$part1);
	}
	private function findLog4Priority($priority)
	{
		$o = null;
		if(isset(self::$path_instances[self::$path.":".$priority]))
			return self::$path_instances[self::$path.":".$priority];
		if(isset(self::$path_instances[self::$path]))
			return self::$path_instances[self::$path];

		list($parsed,$found_w_priority,$found_at_path) = 
			$this->findLogConfigValue(self::$path,"priority",false);

		if(($parsed = @parse_url($to_parse)) === false)
			throw new Log2Exception("Config value '$to_parse' has malformed format");

		if(empty($parsed['scheme']))
			throw new Log2Exception("Logger classname wasn't found");

		$logger_class = "writer".preg_replace("/[^A-Za-z]/",$parsed['scheme']);
		$parsed['params'] = parse_str($parsed['query']);
		unset($parsed['scheme']);
		unset($parsed['query']);
		unset($parsed['fragment']);

		return self::$path_instances[$found_at_path.($found_w_priority?(":".$priority):"")] = 
			self::$path_instances[self::$path.($found_w_priority?(":".$priority):"")] = new $logger_class($parsed);
	}
	
    // {{{ __call
	public function __call($priority, $message) {

		$logger = $this->findLog4Priority($priority);


        if(($k = array_search($priority,$this->priorities)) === false)
			throw new LogException("Priority $priority is not available!");
        $this->writeLog($message[0],$k);
	}
	//}}}
}// }}} 

