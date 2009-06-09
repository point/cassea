<?php
class cmdController extends Command{
    public function __construct( $workingDir = '.', $info, $commandsSeq = array()){
        parent::__construct( $workingDir, $info, $commandsSeq);
        Console::initCore();
    }


    public function cmdLs(){
        Console::initCore();
        $root=Config::get('ROOT_DIR');
        io::out('');
        io::out('Controllers list:');
        $arr = FS::lsExtPhp($root.'/controllers');
        foreach($arr as $c)
            io::out('~GREEN~  '.$c.'~~~');
    }
}
