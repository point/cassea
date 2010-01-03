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
 * This file contains class WriterFile which abstract from WriterAbstract.
 * WriterFile's responsibility is to record log data to a file or a stream.
 *
 * @author Skai <climbonn@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */
//{{{WriterFile
/**
 * WriterFile's responsibility is to record log data to a file or a stream.
 */
class WriterFile extends WriterAbstract
{
    
    /**
     * @var file path or a stream.
     */
    protected $file;
   
    /**
     * @var max file size
     */
    protected $maxsize=104857600;
   
   
    /**
     * @var max file count
     */
    protected $maxcount=10;


    //{{{ __construct
    /**
     * @param file path or a stream
     * @param write mode
     * @return WriteFile object 
     */
    public function __construct($file){
        if(is_array($file))
        {
            $params=$file;
			if(isset($params['target'])) $this->file=$params['target'];
			else throw new LogException("Param 'target' must be in config.ini");
			
			if(isset($params['maxsize'])) $this->maxsize=sizeFromString($params['maxsize']);

			if(isset($params['maxcount'])) $this->maxcount=$params['maxcount'];

		}
		else
			$this->file=$file;
        $this->formatter=new FormatterSimple();
    }
    //}}}

    // {{{ write
    /**
     *
     * Record log data to a file(stream).
     * @param  array $event 
     * @return null 
     */
	public function write($event){
		
		$towrite=$this->formatter->format($event);

        if(is_resource($this->file))
		{
			if(get_resource_type($this->file) != 'stream')
				throw new LogException("ResourceType is a not stream.");
			$file= $this->file;
		}
        elseif(is_string($this->file))
		{
			$file=Config::get("root_dir").Config::get("logs_dir")."/".$this->file;
			if(!is_null($this->maxsize))
                if(filesize($file) > $this->maxsize) $this->fileManager($file);
			$file=fopen($file,"a");
				//throw new LogException("Failed to open stream $this->file");
        }
		
		if(flock($file, LOCK_EX)){
			if(!fwrite($file,$towrite))
				throw new LogException("Failed to write stream $file");
			flock($file, LOCK_UN);
		}
        fclose($file);
    }
    // }}}

	// {{{ __destruct
	/**
	 * Close opened resources
	 */
    public function __destruct(){
    }//}}}
    
     //{{{fileManager
    /**
     * Look for max file size in config. If filesize > max then  packing file into 
     * the tar.bz2 and delete source file.
     */
    private function fileManager($path){
        $name=basename($path);
        $filename=basename($path,".log");
        $dir=dirname($path);
        try{
            chdir($dir);
            
            if(file_exists($dir.'/'.$filename."_".date('Ymd').".bz2")||file_exists($dir.'/'.$filename."_".date('Ymd')))
                $filename=$filename."_".date('Ymd_H_i_s');
            else
                $filename=$filename."_".date('Ymd');
            exec("mv ".$name." ".$filename,$out,$return);
            exec("bzip2 -9 ".$filename." > /dev/null 2>&1 &",$out,$return);
        }catch(Exception $e){echo $e;}
    }//}}}
} //}}} end of class WriterFile
