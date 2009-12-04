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
 * This file contains class DelayedJob.
 *
 * @author Skai <climbonn@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

/*
 Example:
    Autoload::addVendorDir('delayedjob');
    $param=array("text","test");
    $j= new DelayedJob('test',$param);
    //$j->at('2009-08-07 14:07');//Format YYYY-MM-DD HH:MM
    $j->in("2 days");//Format hh,mm
    $j->priority(1);
    $j->attempts(5);
    $j->queue('default');
    $j->add();

	//or using fluent interfaces
    $param=array("text","test");
    $j= new DelayedJob('test',$param);
	$j->at("next Monday")->priority(2)->attempts(5)->queue("default")->add();
*/

//{{{DelayedJob
class DelayedJob{

    const TABLE = "delayed_jobs";
   
    /**
     * @var string. LogFile path.
     */
    private $logFile;
    
    /**
     * @var string. Time for run work.Unix timestamp
     */
    private $at         = null; 
    
    /**
     * @var string. The queue name.
     */
    private $queue      = "default"; 
    
    /**
     * @var int. Priority of work.
     */
    private $priority   = 1; 
    
    /**
     * @var int. Number of attempts.
     */
    private $attempts   = 1; 
    
    /**
     * @var array of all params to work properly. This array consist of class, method, params for method,
     * path to file(class) that contain method.
     */
    private $handler    = array();
            

    // {{{__construct 
    /**
     * Params set with debug_backtrace function help.
     * @param string method to be called.
     * @param array All needed params for given method.
     * @param string class name that contains method.
     * @param path to the file with passed class.
     * @return DelayedJob object.
    */

    function __construct($method,array $param=array(),$class=null,$path=null)
	{
		$root=Config::get("root_dir");
		require_once($root."/includes/Log.php");
        $this->logFile = $root.Config::get('logs_dir').'/delayedjob.log';
		$r=debug_backtrace(false);
        if(empty($class))
			if(isset($r[1]) && isset($r[1]['class']))
				$this->handler['class']=$r[1]['class'];
			else
				throw new DelayedJobException("Parameter class must be setted");
        else
            $this->handler['class']=$class;
        
		if(empty($path))
			if(isset($r[0]) && isset($r[0]['file']))
				$this->handler['file']=$r[0]['file'];
			else throw new DelayedJobException("Parameter path must be setted");
		elseif(!file_exists($path) || !is_readable($path))
			throw new DelayedJobException("File at $path doesn't exists or not readable");
		else
			$this->handler['file']=$path;

        $this->handler['method']=$method;
		if(!empty($param) && is_array($param))
            $this->handler['param']=$param;
    }
    //}}}

    //{{{ at
    /**
     * Set run time for the the job.
	 *
	 * For example <code> $delayed_job->at("next Monday"); </code>
     * @param string date containing a US English date format.
     * @return DelayedJob 
     */
    public function at($at)
	{
		if(empty($at)) return $this;

		if(($_at = strtotime($at)) === false) return $this;
        $this->at=$_at;
		return $this;
    }
	//}}}
	
    //{{{ in
    /**
     * Set time interval to run the job.
	 * 
	 * For example: <code>$delayed_job->in("2 days")</code>
     * @param number of hours.
     * @param number of minutes.
     * @return DelayedJob
     */
    public function in($in)
	{
		$in = trim(trim($in),"+");

		return $this->at("+$in");
    }
    //}}}
    
    //{{{ queue
    /**
     * Set the jobs's queue name.(For example 'mail')
     * @param string queue name.
	 * @retrurn DelayedJob
     */
    public function queue($queue)
    {
		if(empty($queue)) return $this;
		
		$this->queue=strtolower((string)$queue);
		
		return $this;
    }
   //}}}
    
    //{{{ attempts
    /**
     * Set number of attempts.
     * @param number number of attempts.
     * @return DelayedJob
     */
    public function attempts($try)
    {
		if(empty($try) || (int)$try < 1) return $this;
		
		$this->attempts=(int)$try;
		
		return $this;
    }
    //}}}

    //{{{priority
    /**
     * Set priority for the current job.
     * @param int priority. Jobs with higher priority will be processed earlier.
     * @return DelayedJob
     */
    public function priority($priority)
	{
		if(empty($priority) || (int)$priority < 1) return $this;
		
		$this->priority=(int)$priority;

		return $this;
    }
    //}}}
   
    //{{{getAt
    /**
     * Gets run time for the job.
     * @return int unix timestamp
     */
    public function getAt(){return $this->at;}
    //}}}
    
    //{{{getAtSys   
    /**
     *Get time in format for system command "at".
     *@return string
     */
    public function getAtSys()
	{
		return date("H:i m/d/y ",$this->getAt()+60);  
	}
    //}}}

    //{{{getHandler
    /**
     * Get handler for the job.
     * @return arrray of params
     */
    public function getHandler(){return $this->handler;}
    //}}}
        
    //{{{getQueue
    /**
     * Get queue name of the job.
     * @return string. Queue name.
     */
    public function getQueue(){return $this->queue;}
    //}}}
    
    //{{{getAttempts
    /**
     * Get Number of attempts.
     * @return int. Number of attempts.
     */
    public function getAttempts(){return $this->attempts;}
    //}}}
   
    //{{{getPriority
    /**
     * Get priority of the job.
     * @return int
     */
    public function getPriority(){return $this->priority;}
    //}}}
    
    //{{{add
    /**
     * Insert into database all fields need to work. Run the JobHandler immediatly if 
     * unset run time. Else run JobHandler with system command 'at' with set time.
	 * @param null
     * @return null
     */
    public function add()
    {
        $config_php_path = Config::get("php_cli_path");
		
		$php_path = exec("which php");
		if(empty($php_path) || !is_executable($php_path))
			$php_path = $config_php_path; // Config::get("php_path");

		if(empty($php_path) || !is_executable($php_path))
		{
			Log::get('dj')->error("PHP executable not found or could not be executed");
			throw new DelayedJobException("PHP executable not found");
		}

        if(empty($this->at))
        {
			$sql="INSERT INTO ".self::TABLE."(priority,handler,run_at,queue,attempts) values( ? , ? , now() , ? , ?)";
			$stmt=DB::getStmt($sql,'issi');
			$stmt->execute(array($this->getPriority(),serialize($this->getHandler()),$this->getQueue(),$this->getAttempts()));
			Log::get('dj')->info(" =======================");
			Log::get('dj')->info(" Call the JobHandler.php");
            exec($php_path.' '.trim(escapeshellarg(dirname(__FILE__)."/JobHandler.php"),"'").' >> '.$this->logFile.' 2>&1 &');
        }
        else
        {
			$sql="INSERT INTO ".self::TABLE."(priority,handler,run_at,queue,attempts) values(? , ? , FROM_UNIXTIME( ? ) , ? , ? )";
			$stmt=DB::getStmt($sql,'isssi');
			$stmt->execute(array($this->getPriority(),serialize($this->getHandler()),$this->getAt(),$this->getQueue(),$this->getAttempts()));
			Log::get('dj')->info(" =======================");
			Log::get('dj')->info(" Call command at for the JobHandler.php");
			Log::get('dj')->info(" =======================");
            exec('echo "'.$php_path.' '.trim(escapeshellarg(dirname(__FILE__).'/JobHandler.php'),"'").' 2>&1 >> '.$this->logFile.' " | at '.$this->getAtSys());
        }
    }
    //}}}
    
}
//}}}
