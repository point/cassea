<?php
class CmdSwitch extends Command{
    
    function process()
    {        
        if (!($c = ArgsHolder::get()->shiftCommand())) 
            return io::out('Incorrect parameter', IO::MESSAGE_FAIL) | 1;

        $root=Config::get('ROOT_DIR');

        $file=$root.'/includes/env/'.strtolower($c).'_env.php';
        if (!file_exists($file))
            return io::out('Mode '.$c.' not exists', IO::MESSAGE_FAIL) | 1;

        IO::out('Updating Loader', false);
        $loader=fopen($root.'/includes/env/Loader.php','w');
        flock($loader,LOCK_EX);
        $put='<?php require_once("'.$c.'_env.php");';
        fwrite($loader,$put);
        flock($loader,LOCK_UN);
        fclose($loader);
        io::done();

        io::out('Backup config.ini',false);
        if (copy($root.'/config/config.ini', $root.'/config/config.ini.bak'))io::done();
        else return IO::out('Can\'t backup file config.ini', IO::MESSAGE_FAIL) | 1;


        IO::out('Updating config.ini', false);
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
        io::done();

        IO::done('Enviroments set to ~WHITE~'.$c.'~~~'); 
    }
}
?>
