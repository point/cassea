<?php


class CmdGroup extends Command{
      
    public function __construct( $workingDir = '.', $info, $commandsSeq = array()){
        parent::__construct( $workingDir, $info, $commandsSeq);
        Console::initCore();
    }
}
