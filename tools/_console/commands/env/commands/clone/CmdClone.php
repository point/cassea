<?php
class CmdClone extends Command{
    
    function process()
    {        
        $root=Config::get('ROOT_DIR');
        if (($src = ArgsHolder::get()->shiftCommand())=== false ||( $new = ArgsHolder::get()->shiftCommand()) === false)
            return io::out('Incorrect param count', IO::MESSAGE_FAIL) | 1;
        $src_file=$root.'/includes/env/'.strtolower($src).'_env.php';
        $new_file=$root.'/includes/env/'.strtolower($new).'_env.php';
        if (file_exists($new_file))
            return io::out( 'Enviroments ~WHITE~'.$new.'~~~ already exists!',IO::MESSAGE_FAIL) | 2;
        if (!file_exists($src_file))
            return io::out( 'Source enviroments ~WHITE~'.$src.'~~~ is not exists!',IO::MESSAGE_FAIL)| 2;


        io::out('Writing files',false); 
        if (!copy($src_file,$new_file))
            return io::out('Unable copy env files ('.$src_file.' to '.$new_file.')', IO::MESSAGE_FAIL) | 3;
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
        $r = fwrite($config,$str);
        flock($config,LOCK_UN);
        fclose($config);
        if (!$r) return io::out('Cant write ~WHITE~config.ini~~~', IO::MESSAGE_FAIL) | 4;


        io::done();

    }
}
?>
