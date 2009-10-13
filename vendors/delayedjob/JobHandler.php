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
 * This file contains class JobHandler.
 *
 * @author Skai <climbonn@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

require_once(dirname(__FILE__)."/DelayedJob.php");
require_once(dirname(dirname(dirname(__FILE__))).'/includes/Boot.php');

//{{{JobHandler
class JobHandler{

    /**
     * @var string. LogFile path.
     */
    private $logFile     = null;
    
    /**
     * @var id of work
     */
    private $id          = null;
    
    /**
     * @var string. The queue name.
     */
    private $queue       = null;
    
    /**
     * @var array of all params to work properly. This array consist of class, method, params for method,
     * path to file(class) that contain method.
     */
    private $handler     = array();
    
    /**
     * @var int. Number of attempts.
     */
    private $attempts    = null;
    
    /**
     * @var string. Time for run work.Format YYYY-MM-DD HH:MM.
     */
    private $run         = null;

    //{{{__construct
    function __construct()
    {
		//TODO substitute with logger. Will be deleted
        
        $this->logFile = Config::get('root_dir').Config::get('logs_dir').'/delayedjob.log';
        set_error_handler(array($this,'DJErrorHandler'),E_ERROR);
		$r=DB::query('SELECT * FROM '.DelayedJob::TABLE.' where run_at <= now() and isnull(finished_at) and isnull(locked_at) and isnull(failed_at) ORDER BY priority FOR UPDATE');
        if(count($r))
        {
            if($this->queueCheck())
            {
                DB::query('update '.DelayedJob::TABLE.' set locked_at="'.date("Y-m-d H:i:s").'" where id="'.$this->id.'"');
                $this->log("--- ".date('c')." Cheking the queue.Queue ".$this->queue." is free");
				$this->id       = $r[0]['id'];
				$this->queue    = $r[0]['queue'];
				$this->attempts = $r[0]['attempts'];
				$this->handler  = unserialize($r[0]['handler']);
            }
            else
            {
                $this->log(">>> ".date('c')." Other process try to run... Queue ".$this->queue." is locked![ EXIT ]");
                die('Queue is locked');
            }
        }
        else {
            $this->log(">>> ".date('c')." NOT AT TIME OR QUEUE IS LOCKED![ FAILED ]");
            die('Not At Time or queue is locked');
        }
    }
    //}}}
 
    //{{{DJErrorHandler
    public function DJErrorHandler($errno, $errstr, $errfile, $errline)
    {
        //DB::query('update '.DelayedJob::TABLE.' set handler="'.$errno." ".$errstr." ".$errfile." ".$errline.'" where id="'.$this->id.'"');
        $this->log(date('c').$errno." ".$errstr." ".$errfile." ".$errline);
        DB::query('update '.DelayedJob::TABLE.' set failed_at="'.date("Y-m-d H:i:s").'" where id="'.$this->id.'"');
        $this->log("--- ".date('c')." Command was completed  with error![ FAILED ]");
    }
    //}}}
    
    //{{{++++++++++log need to use Logger
    private function log($str)
    {
        //exec("echo '".$str."'  >> ".$this->logFile);
        file_put_contents($this->logFile, PHP_EOL.$str ,FILE_APPEND);
    }
    //}}}
    
    //{{{doNow
    public function doNow()
    {
        if(!isset($this->id))throw new JobHandlerException("Jobs doesn't exists");
        DB::query('update '.DelayedJob::TABLE.' set locked_at="'.date("Y-m-d H:i:s").'" where id="'.$this->id.'"');
        include_once($this->handler['file']);
        
        $this->log("\n");
        $this->log(">>> ".date('c')." DelayedJob start working.Class ".$this->handler['class']." run the method ".$this->handler['method']."()<<<");
        $this->log("--- ".date('c')." Cheking the queue.Quie ".$this->queue." is free");

        $counter=$this->attempts;
        while($counter>0)
        {
            try{
                $this->log("--- ".date('c')." There are ".$counter." attempts.");
                DB::query('update '.DelayedJob::TABLE.' set attempts=attempts-1 where id="'.$this->id.'"');
                $counter--;
                $ret=call_user_func_array(array($this->handler['class'],$this->handler['method']),$this->handler['param']);
                if(!$ret)
                {
                    DB::query('update '.DelayedJob::TABLE.' set failed_at="'.date("Y-m-d H:i:s").'" where id="'.$this->id.'"');
                    $this->log("--- ".date('c')." Command was completed  with error (". var_export($ret,true).")![ FAILED ]");
                }
                else 
                {
					DB::query('update '.DelayedJob::TABLE.' set finished_at="'.date("Y-m-d H:i:s").
						'", failed_at = null where id="'.$this->id.'"');
                    $this->log("--- ".date('c')." Command was completed successfully![ OK ]");
                    break 1;
                }
            }catch(Exception $e)
                {
                    $this->log(date('c').' Work is Fail!');
                    $this->log(' Message: '.$e->getMessage());
                    continue;
                }
        }

    }
    //}}}

     //{{{worker
    public function worker()
    {
		$this->doNow();
		$sql = 'SELECT * FROM '.DelayedJob::TABLE.' where queue="'.$this->queue.'" and run_at<= now() '.
			' and isnull(finished_at) and isnull(locked_at) and isnull(failed_at) ORDER BY priority limit 1';

		$r = DB::query($sql);
        while(count($r))
        {
            $this->id       = $r[0]['id'];
            $this->queue    = $r[0]['queue'];
            $this->attempts = $r[0]['attempts'];
            $this->handler  = unserialize($r[0]['handler']);
            $this->doNow();
			$r=DB::query($sql);
        }
    } 
    //}}}
    
    //{{{queueCheck
    public function queueCheck()
    {
		$queue=DB::query('SELECT id FROM '.DelayedJob::TABLE.' where queue="'.$this->queue.
			'" and id<>"'.$this->id.'" and isnull(finished_at) and not isnull(locked_at) and isnull(failed_at) limit 1');
        if(empty($queue))
            return true;
        else
            return false;
    }
     //}}}
    
}
//end of class JobHandler
error_reporting(-1);
$j= new JobHandler;
$j->worker();
?>
