<?php

class RollbackException extends CasseaException{}


class RollBack{
    private $file;
    private $list = array();

    // {{{ __construct
    /**
     */
    function __construct($path = null){
        $this->file = is_null($path)?PM::getRollbackDir()->getFile('rollback.txt'):$path;
        if (!is_writable($this->file)) throw new RollbackException('Rollback file ('.$this->file.') not writable.');
    }// }}}

    function clean(){
        IO::out('>>>Rollbak clean: '.$this->count() ,false);
        file_put_contents($this->file,'');
        io::done();
    }

    function count(){
        return count(file($this->file));
    }

    function stepBack(){
        if (!$this->count()) return;

        io::out('>>>Rollback '.($c= $this->count()).' actions');
        for ($i =0, $c= $this->count(); $i < $c; $i++){
            try{
                if ($this->processStep($this->top())) $this->pop();
            }catch (Exception $e){
                echo $e;
                die();
            }
        }
    }

    // {{{ processStep
    /**
     * Обработка одного шага.
     *
     * @throw RollbackException
     * @param array $step
     */
    function processStep($step){
        if (!count($step)) return;
        switch ($cmd = array_shift($step)){
        case 'delete': $this->delete($step); break;
        case 'move': break;
        case 'undeploy': Deployer::undeploy(Dir::get($step[0], true)); break;
        default:throw new RollbackException('Unknown command '.$cmd);
        }
        return true;

    }// }}}

    function delete($what){

        io::out( 'Deleting: ');
        foreach($what as $t){
            echo $t;
            if (file_exists($t)){
                if (is_dir($t)) $t = new Dir($t,true);
                else $t = new File($t, true);
                $r = $t->delete();
                io::done();

            }
            else echo " Not found.";
            echo PHP_EOL;
        }
    }

    function pop(){
        $l = file($this->file);
        if(!count($l)) return array();
        $r = explode('||',trim(array_pop($l)));
        if (file_put_contents($this->file, implode('',$l))=== false)
            throw new RollbackException('Error while "popping" step to file '.$this->file);
        $this->count = $l;
        return $r;
    }

    function top(){
        $f = file($this->file);
        return explode('||',trim(array_pop($f)));
    }

    
    function push(){
        $args =  func_get_args();
        //io::out('>>>RollBack push:  '.implode(',  ',$args));
        if (!file_put_contents($this->file, implode("||",$args).PHP_EOL,FILE_APPEND))
            throw new RollbackException('Error while adding step to file '.$this->file);
        $this->count++;
    }


}
