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
 * @author Skai <climbonn@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

Autoload::addDir(dirname(__FILE__).'/logger');

//{{{Log
/**
 * Log make it possible to log all events in system. It provide to log the message with
 * different priorities to the different log file, E-mails, tables in DB, Jabber.
 * And also to filter messages by priority or message content.
 *
 * Before writing message of event, formatter return the formatted message(text line or xml).
 * All function are containing into the objects: Writers, Filters, Formatters.
 * The Log object must contain at least one Writer, and can contain one or more Filters.
 */
class Log
{
	const CRITICAL  = 0;
	const ERROR		= 1;
	const WARNING   = 2;
	const NOTICE    = 3;
	const INFO      = 4;
	const DEBUG     = 6;
	
	const INI       = "logger.ini";
    
    /**
     * @var array. Array of priorities where the keys are the
     * priority numbers and the values are the priority names
     */
    protected $priorities   = array(
        0=>'critical',
        1=>'error',
        2=>'warning',
        3=>'notice',
        4=>'info',
        5=>'debug'
        );
    
    /**
	 * List of Fileters . its'll be applied to every message.
	 *
     * @var array of  FiltersInterface
     */
    protected $filters     = array();
    
    /**
     * @var array Array of objects WritersAbstract
     */
    protected $writers      = array();
    
    /**
     * @var array Array of extras that can be in the event
     */
    protected $extras = array();
    
    /**
     * @var object Log object or null. Using in static method get
     */
    private static $instance;

    // {{{__construct 
    /**
     * @param object WriterAbstract
     * @return Log object
     */
    public function __construct(WriterAbstract $writer = null) {
        if(!is_null($writer))
            $this->addWriter($writer);
    }// }}}

    // {{{ get
    /**
	 * Select options for logging events from config with specified name. 
	 * If name is null - select options for default.
	 * 
	 * @param string name of part that need to log. 
     * @return object of class Log
     * @throws LogException if name isn't a string or option with this name doesn't exists in config.ini
     */
	public static function get($name = 'default') {
        if (isset(self::$instance[$name]))
			return self::$instance[$name];
		
		$log = new Log();
		$parse=array();

		try{
			$writersConfig=parse_ini_file(Config::get("root_dir")."/config/".self::INI,true);
			if (empty($writersConfig[$name])) return self::$instance[$name] = new FakeLog();
			foreach($writersConfig[$name] as $k=>$v)
				$parse[$name][strtok($k,'.')][strtok('.')]=$v;

			foreach($parse[$name] as $writerName => $writerConfig){
				$class='Writer'.ucwords($writerName);
				$log->addWriter(new $class($writerConfig));
			}
		}catch(Exception $e){}

        return self::$instance[$name] = $log;
    }// }}}
	
	// {{{ addWriter
	/**
	 * Add writer to the array of objects for multiple sending message from event.
	 * @param object of WriterAbstract
	 * @return null
	 */
	public function addWriter(WriterAbstract $writer) {
		$this->writers[]=$writer;
	}// }}}
    
    // {{{ addExtras
    /**
     * Set extra items.
     * @param key of extras
     * @param value of extras
     * @return null
     * */
    public function addExtras($key,$value){
        $this->extras[$key]=$value;
    }// }}}
    
    //{{{ addFilter
    /**
	 * Add filter to the array of objects for filtering events 
	 * by priority or  by regular expression(for event's message).
	 *
     * @throws LogException if filter isn't a string or a integer.
     * @param string|integer|iLogFilter 
     */
	public function addFilter($filter) {
		switch(gettype($filter)) {
			case 'integer':	$filter = new FilterPriority($filter); break;
			case 'string':	$filter = new FilterMessage($filter);	break;
			case 'object':	if( !($filter instanceof iLogFilter)) break;
			default: throw new LogException("Is not a filter");
		}
		$this->filters[] = $filter;
    }// }}}

    // {{{ writeLog
    /**
     * Record(send) event's message using writers. If priority <=3, writerMail will send this message to the admin(s).
     * @param string $message Message to log 
     * @param int $priority  Priority of message
     * @return null
     * @throws LogException if priority doesn't exists
     */
    public function writeLog($message, $priority) {
        if(!isset($this->priorities[$priority]))
			throw new LogException("Priority $priority is not available!");
        $event=array_merge(array(
                    "time"=>date("c"),
                    "message"=> $message,
                    "priority"=>$priority,
                    "priorityName"=>$this->priorities[$priority]
                ), $this->extras
            );
		
		foreach($this->filters as $filter)
            if(!$filter->accept($event)) return;

        foreach($this->writers as $writer)
            $writer->write($event);
    }// }}}

    // {{{ __call
    /**
     * Method allows a shortcut form of: 
     * <code>
     * $log= new Log($writer);
     * $log->writeLog("message!",3);
     * </code>
     * Example:
     * <code> 
     * $log->error("Error message!");
     * </code>
     *
     * @param string $priority 
     * @param string $message 
     * @return null
     * @throws LogException if doesn't exists a priority
     */
	public function __call($priority, $message) {
        if(($k = array_search($priority,$this->priorities)) === false)
			throw new LogException("Priority $priority is not available!");
        $this->writeLog($message[0],$k);
    }// }}}
}// }}} 

