<?php
class CmdSwitch extends Command{
    
    function process()
    {        
        Console::initCore();
        $root=Config::get('ROOT_DIR');
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        try{

            copy($root.'/config/config.ini', $root.'/config/config.ini.bak');
            $file=$root.'/includes/env/'.strtolower($c).'_env.php';
            if (!file_exists($file))
                {
                   io::out('Mode '.$c.' not exists', IO::MESSAGE_FAIL);
                   return;
                }
            
            $loader=fopen($root.'/includes/env/Loader.php','w');
            flock($loader,LOCK_EX);
            $put='<?php require_once("'.$c.'_env.php");';
            fwrite($loader,$put);
            flock($loader,LOCK_UN);
            fclose($loader);

            $file_array =file($root.'/config/config.ini');
            $str=null;
            foreach($file_array as $fa)
            {
                if(preg_match('/\[\s*config\s*:\s*\S+\]/',$fa,$match))
                    $str.='[config : '.$c.']'.PHP_EOL;
                else
                    $str.=$fa;
            } 
            $config=fopen($root.'/config/config.ini','w');
            flock($config,LOCK_EX);
            fwrite($config,$str);
            flock($config,LOCK_UN);
            fclose($config);

        }catch(exception $e){
         io::out( $e->getmessage(),IO::MESSAGE_FAIL);
         return;
        }
        IO::done('Set mode '.$c.' for site'); 
    }
}
?>
