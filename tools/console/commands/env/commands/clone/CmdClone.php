<?php
class CmdClone extends Command{
    
    function process()
    {        
        Console::initCore();
        $root=Config::get('ROOT_DIR');
        if (($src = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        if (($src=== false)||(strpos($src,'.')))
        {
            io::out('Incorrect param count', IO::MESSAGE_FAIL);
            return;
        }
        $new = ArgsHolder::get()->shiftCommand();
        if (($new=== false)||(strpos($new,'.')))
        {
            io::out('Incorrect param count', IO::MESSAGE_FAIL);
            return;
        }
        $src_file=$root.'/includes/env/'.strtolower($src).'_env.php';
        $new_file=$root.'/includes/env/'.strtolower($new).'_env.php';
        if (file_exists($new_file))
        {
            io::out( '~RED~'.$new.'~~~ exist!',IO::MESSAGE_FAIL);
            return;
        }
        if (!file_exists($src_file))
        {
            io::out( '~RED~'.$src.'~~~ is not exist!',IO::MESSAGE_FAIL);
            return;
        }
        
        try{
            copy($src_file,$new_file);
            $file_array =file($root.'/config/config.ini');
            $str=null;
            foreach($file_array as $fa)
            {
                if(preg_match('/\[\s*config\s*:\s*\S+\]/',$fa,$match))
                     $str.='['.$new.': base ]'.PHP_EOL.$match[0].PHP_EOL;
                else
                    $str.=$fa;
            } 
            $config=fopen($root.'/config/config.ini','w');
            flock($config,LOCK_EX);
            fwrite($config,$str);
            flock($config,LOCK_UN);
            fclose($config);

        }catch(exception $e)
        {
         io::out( $e->getmessage(),IO::MESSAGE_FAIL);
         return;
        }

        io::done('Copying mode');

    }
}
?>
