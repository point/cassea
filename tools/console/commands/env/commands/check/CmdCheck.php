<?php
class CmdCheck extends Command{
    protected 
        $for_check=array(
            'register_long_arrays'             => '0',
            //'log_errors'                       => '1',
            //'display_errors'                   => '1',
            //'error_reporting '                 => '0',
            //'error_log'                        => '',
            'output_buffering'                 => '4096',
            'register_argc_argv'               => '0',
            'magic_quotes_gpc'                 => '0',
            'variables_order'                  => 'GPCS',
            'allow_call_time_pass_reference'   => '0',
            'short_open_tag'                   => '0',
            'safe_mode'                        => '0',
            'open_basedir'                     => '0',
            'max_execution_time'               => '15',
            'max_input_time'                   => '30',
            'memory_limit'                     => '64',
            'display_startup_errors'           => '0',
            'register_globals'                 => '0',
            'post_max_size'                    => '8',
            'upload_max_filesize'              => '2',
            'magic_quotes_runtime'             => '0',
            'default_mimetype'                 => 'text',
            'default_charset'                  => 'utf-8',
            'file_uploads'                     => '1',
            'allow_url_fopen'                  => '1',
            'allow_url_include'                => '0',
            'mbstring.language'                => 'Neutral',
            'mbstring.internal_encoding'       => 'UTF-8',
            'mbstring.http_input'              => 'UTF-8,CP1251,KOI8-R',
            'mbstring.http_output'             => 'UTF-8',
            'mbstring.encoding_translation'    => '1',
            'mbstring.func_overload'           => '6',
        ),
        
        $ext=array(
            'curl',
            'pcre',
            'SimpleXML',
            'SPL',
            'dom',
            'fileinfo',
            'json',
            'mbstring',
            'mysqli',
            'posix',
            'session',
            'sockets',
            'libxml',
            'apc',
            'gd',// 'imagick',
            'memcache'
        ),
        
        $mod_production=array(
            'log_errors'                       => '1',
            'display_errors'                   => '0',
            'error_reporting '                 => '0',
        ),
        
        $mod_dev=array(
            'log_errors'                       => '0',
            'display_errors'                   => '1',
            'error_reporting '                 => '-1',
        ),
        $generate =null;
    
    protected function processOptions()
    {
        if (ArgsHolder::get()->getOption('generate-ini'))
            $this->generate = true;
    }


    function process()
    {        
        Console::initCore();
        $root=Config::get('ROOT_DIR');
        $file_array =file_get_contents($root.'/config/config.ini');
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        try{
            IO::out('');
                $toini=array();
                foreach($this->for_check as $k=>$v)
                {
                    $output=array();
                    $subcmd='<?php $r=ini_get("'.$k.'");if($r) echo $r; else echo ((int)$r);';
                    exec("echo '".$subcmd."' | php-cgi -q",$output);
                    if($k=='error_log')
                        {IO::out($k.' = '.$output[0],IO::MESSAGE_OK);continue;}
                    
                    if(($k=='error_log')||($k=='max_execution_time')||($k=='max_input_time')||($k=='upload_max_filesize')||($k=='memory_limit')||($k=='post_max_size'))
                    {
                        $ch=str_replace('m','',strtolower($output[0])); 
                        if((int)$ch>=(int)$v)
                            {IO::out($k.' = '.$output[0],IO::MESSAGE_OK);continue;}
                        else
                            {io::out($k.' = '.$output[0].' this must be >='.$v,IO::MESSAGE_FAIL);continue;}
                    }

                    if($v==$output[0])
                        IO::out($k.' = '.$output[0],IO::MESSAGE_OK);
                    else
                    {
                        $out=array();
                        $subcmd='<?php ini_set("'.$k.'","'.$v.'");$r=ini_get("'.$k.'");if($r=="'.$v.'") echo 111; else echo print_r($r);';
                        exec("echo '".$subcmd."' | php-cgi -q",$out);
                        if($out[0]==111)
                        {
                            $toini[$k] = $v;
                            IO::out($k.' = '.$output[0].', but you can set "'.$v.'"',IO::MESSAGE_WARN);
                        }
                        else
                            io::out($k.' = '.$output[0],IO::MESSAGE_FAIL);
                    }
                }
                
                $output=array();
                $subcmd='<?php $r=ini_get("upload_max_filesize");if($r) echo $r; else echo ((int)$r);';
                    exec("echo '".$subcmd."' | php-cgi -q",$output);
                $umf=str_replace('m','',strtolower($output[0]));    
                
                $output=array();
                $subcmd='<?php $r=ini_get("post_max_size");if($r) echo $r; else echo ((int)$r);';
                    exec("echo '".$subcmd."' | php-cgi -q",$output);
                $pms=str_replace('m','',strtolower($output[0]));    
                $output=array();
                $subcmd='<?php $r=ini_get("memory_limit");if($r) echo $r; else echo ((int)$r);';
                    exec("echo '".$subcmd."' | php-cgi -q",$output);
                $ml=str_replace('m','',strtolower($output[0]));    
                if((int)$pms < (int)$umf)
                    IO::out('post_max_size < upload_max_filesize',IO::MESSAGE_FAIL);
                if((int)$pms > (int)$ml)
                    IO::out('post_max_size > memory_limit',IO::MESSAGE_FAIL);
                
                if(((int)$pms>=(int)$umf)&&((int)$pms<(int)$ml))
                    IO::out('upload_max_filesize('.$umf.')=< ini.post_max_size('.$pms.') < ini.memory_limit('.$ml.')',IO::MESSAGE_OK);
               
                if(is_writable('/tmp'))
                    IO::out('Temp dir is writable.',IO::MESSAGE_OK);
                else
                    IO::out('Temp dir is not writable!',IO::MESSAGE_FAIL);


                IO::out();
                IO::out('Check for extensions:');
                exec("php-cgi -m",$output);
                foreach($this->ext as $e)
                {
                    if($e=='gd')
                    {
                        if(!in_array($e,$output))
                            if(!in_array('imagick',$output))
                                io::out('Extension '.$e.' or imagick are not loaded.',IO::MESSAGE_FAIL);
                            else
                                io::out('Extension imagick is loaded.',IO::MESSAGE_OK);
                        else
                            io::out('Extension '.$e.' is loaded.',IO::MESSAGE_OK);
                    continue;
                    }

                    if(!in_array($e,$output))
                        io::out('Extension '.$e.' is not loaded.',IO::MESSAGE_FAIL);
                    else
                        if($e=='apc')
                        {io::out('Extension '.$e.' is loaded.You can set "apc.enabled=On" in php.ini.',IO::MESSAGE_OK);continue;}
                        else
                        io::out('Extension '.$e.' is loaded.',IO::MESSAGE_OK);
                }
                
                IO::out();
                IO::out('Check for modes:');
                IO::out('Check for Production:');
                foreach($this->mod_production as $k=>$v)
                    {
                        $output=array();
                        $subcmd='<?php ini_set("'.$k.'",'.(int)$v.');$r=ini_get("'.$k.'");if($r=='.$v.') echo 1; else echo (int)$r;';
                        exec("echo '".$subcmd."' | php-cgi -q",$output);
                        if($output[0])
                            IO::out($k.' = '.$v,IO::MESSAGE_OK);
                        else
                            IO::out($k.' = '.$output[0],IO::MESSAGE_FAIL);


                    }

                IO::out();
                IO::out('Check for Development:');
                foreach($this->mod_dev as $k=>$v)
                    {
                        $output=array();
                        $subcmd='<?php ini_set("'.$k.'",'.(int)$v.');$r=ini_get("'.$k.'");if($r=='.$v.') echo 1; else echo (int)$r;';
                        exec("echo '".$subcmd."' | php-cgi -q",$output);
                        if($output[0])
                            IO::out($k.' = '.$v,IO::MESSAGE_OK);
                        else
                            IO::out($k.' = '.$output[0],IO::MESSAGE_FAIL);

                    }


                
                if($this->generate)
                {
                    if(!empty($toini))
                    {
                        $res=array();
                        if(file_exists(Config::get('ROOT_DIR').'/php.ini'))
                        {
                            $file=file(Config::get('ROOT_DIR').'/php.ini');
                            for($i=0;$i<count($file);$i++)
                                if(($file[$i]!="\n"))
                                    $res[strtok($file[$i],'=')]=strtok('=');
                        }

                        foreach($toini as $k=>$v)
                        {
                            if(!isset($res[$k]))
                                $res[$k]=$v;
                            if($res[$k]!=$toini[$k])
                                $res[$k]=$toini[$k];
                        }

                        foreach($res as $key=>$val)
                            $write[]=$key.'='.$val;

                        $f=fopen(Config::get('ROOT_DIR').'/php.ini','w');
                        if (flock($f, LOCK_EX)) 
                        {
                            fwrite($f,implode("\n",$write));
                            flock($f, LOCK_UN);
                        } else 
                            echo "Couldn't get the lock!";
                        fclose($f);

                    }
                }

        }catch(exception $e){
         io::out( $e->getmessage(),IO::MESSAGE_FAIL);
         return;
        }
        IO::out();
        IO::done('Ckecking completed successfully.'); 
    }
}
?>
