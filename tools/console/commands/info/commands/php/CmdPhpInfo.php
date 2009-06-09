<?php
class CmdPhpInfo extends Command{
    
    function process()
    {        
       
        Console::initCore();
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        if (!$c) return $this->configInfo();
        if ($c=='ext')
        { 
            $ext = ArgsHolder::get()->shiftCommand();
            if($ext)
                return $this->infoByExt($ext);
        }
        else
        {
           io::out('Incorrect param '.$c, IO::MESSAGE_FAIL);
           return;
        }
            
        try{
            $this->allExtInfo();
        }catch(Exception $e){
            echo $e;
        }
    }


    public function allExtInfo()
    {
        $ext=$this->extInfo();
        foreach($ext as $k=>$v)
            if($k<>"")
                $this->infoByExt($k);

    }
    public function configInfo()
    { 
        ob_start(); 
        phpinfo(4);
        $s = ob_get_contents(); 
        ob_end_clean(); 
        
        $NL =  "\n";
        $lines = explode($NL, $s);
        $c=count($lines);
        $out=array();
        for($i=0;$i<$c;$i++)
        {
            if($lines[$i]<>"")
                $out[]= explode(' => ', $lines[$i]);
        }
        io::out("");
        for($i=1;$i<count($out);$i++)
            if(isset($out[$i][1])&& $out[$i][1]<>'no value'){
                if (preg_match('!#[0-9A-F]{6}!',$out[$i][1], $m)) $out[$i][1] = $m[0];
                if($out[$i][0]=='Directive')
                {io::out(sprintf("%-65s %s","~WHITE~".$out[$i][0]."~~~","~WHITE~".$out[$i][1]."~~~"));io::out("");}
                else
                    io::out(sprintf("%-55s %s",$out[$i][0],"~CYAN~".$out[$i][1]."~~~"));
            }
        io::out("");
    }
    
    public function infoByExt($ext)
    {
        $info=$this->extInfo();
        if (isset($info[$ext]))
        {
            $extinfo=$info[$ext];
            io::out('');
            io::out('~WHITE~Information about extension '.$ext.":~~~");
            foreach($extinfo as $ei)
                    foreach($ei as $k=>$v)
                        if(is_array($v)){
                            array_pop($v);
                            foreach($v as $kk=>$vv)
                                io::out(sprintf("%-40s %s",$k,"~CYAN~".$vv."~~~"));
                        }
                        else
                                io::out(sprintf("%-40s %s",$k,"~CYAN~".$v."~~~"));

            io::out('');
            //print_r($info[$ext]);
        }
        else 
           io::out('No such extension  '.$ext, IO::MESSAGE_FAIL);
    }

    public function extInfo()
    { 
        ob_start(); 
        phpinfo(8);
        $s = ob_get_contents(); 
        ob_end_clean();

        //print_pre($s); die ();

        $NL =  "\n";

        $lines = explode($NL, $s);
        $c= count($lines) + 1;

        $list = get_loaded_extensions();
        $list[] = 'Module Name';

        //print_pre($list); die();

        $res = array();

        $current_ext = null;
        while( ($c--) >0 ){
            $l = array_shift($lines);
            $l = trim($l);
            if (empty($l)) continue;
            
            if (  in_array($l, $list)) $current_ext = $l;
            else{
                $a = explode(' => ', $l);

                if ($current_ext =='Module Name') print_pre($a);

                switch (count($a)){
                case 2:$res[$current_ext]['opt'][trim($a[0])] = trim($a[1]); break;
                case 3:if($a[0] != 'Directive' )$res[$current_ext]['opt2'][array_shift($a)] = $a;

                    break;
                default: $res[$current_ext]['info'][] = $l;

                }
                
            }
        }
        return $res;
    }

}
?>
