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
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */
//{{{ FileLogger
/**
 */
class FileLogger extends AbstractLogger implements iLog2Logger
{
    
    /**
     * @var file path or a stream.
     */
	protected 
		$fp = null,
		$file_path = null,
		$logs_buffer = array();
;
   
    //{{{ __construct
    /**
     * @param file path or a stream
     * @param write mode
     * @return WriteFile object 
     */
	public function __construct($params)
	{
		$this->file_path = $params['path'];
		if($this->file_path = basename($this->file_path))
			$this->file_path = Config::get("root_dir")."/".Config::get("logs_dir")."/".$this->file_path;
		if(!is_writeable($dir_name = dirname($this->file_path)))
			throw new Log2Exception("Directory $dir_name is not writeable");
		
		$this->fp = fopen($this->file_path,"a");
		if(!$this->fp)
			throw new Log2Exception("Cound not open file {$this->file_path} for appending");

		parent::__construct();
		
    }
    //}}}

	public function log(array $params)
	{
		$this->logs_buffer[] = $this->formatString($params);
		$this->try_write();
	}

	private function try_write()
	{
		if (flock($this->fp, LOCK_EX | LOCK_NB))
		{
			foreach($this->logs_buffer as $v)
				fwrite($this->fp,$v);
			$this->logs_buffer = array();
			flock($this->fp,LOCK_UN);
		}
	}

	function __destruct()
	{
		fclose($this->fp);
	}

} 
//}}} 
