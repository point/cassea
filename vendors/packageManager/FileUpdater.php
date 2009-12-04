<?php


class FileUpdater{
    const DELLIST = '.delete_list';
    const RENAME_LIST = '.rename_list';

    private $source;
    private $target;
    private $backup;

    function __construct(iDir $source, iDir $target, iDir $backup){
        $this->source = $source;
        $this->target = $target;
        $this->backup = $backup;
    }

    function run(){
        $dl = new FileDeleter($this->source->getFile(FileUpdater::DELLIST), $this->target, $this->backup);
        $dl->del();

        $fm = new FileMover($this->source->getFile(FileUpdater::RENAME_LIST), $this->target, $this->backup->getFile(FileUpdater::RENAME_LIST));
        $fm->rename();

        $fc = new FileCopier($this->source, $this->target, $this->backup);
        $fc->run();

    }

    function undo(){
        $fc = new FileCopier($this->source, $this->target, $this->backup);
        $fc->undo();

        $fm = new FileMover($this->backup->getFile(FileUpdater::RENAME_LIST), $this->target);
        $fm->rename();
    }

}

// {{{ FileCopier
/**
 *
 */
class FileCopier{
    private $source;
    private $target;
    private $backup;
    private $delList;

    function __construct(iDir $source, iDir $target, iDir $backup){
        $this->source = $source;
        $this->target = $target;
        $this->backup = $backup;
        $this->delList = $this->backup->getFile(FileUpdater::DELLIST);
    }

    public function run(){
        $this->copy($this->source, $this->target, $this->backup);
    }

    public function undo(){
        if ($this->delList->exists()){
            $dl = new FileDeleter($this->delList, $this->target);
            $dl->del();
            $this->delList->delete();
        }
        io::info("\t[~PURPLE~U~~~] ".substr($this->target,strlen(Config::getInstance()->root_dir)));
        $this->backup->copy($this->target, false);
    }

    // {{{ copy
    /**
     *
     */
    function copy(iDir $source, iDir $target, iDir $backup){
        if (!$backup->exists()) mkdir($backup);

        foreach( glob($source.'/*') as $full_path){
            $name = substr($full_path,strlen($source)+1);

            if (is_file($full_path)){
                //TODO compare file by md5_file and copy only different.
                $tf = $target->getFile($name);
                if ($tf->exists()){ 
                    if($tf->copy($backup->getFile($name))) $act = 'U';
                    else io::out('11throw Exception', IO::MESSAGE_FAIL);
                }
                else{
                    $act = 'A';
                    touch($this->delList);
                    file_put_contents($this->delList, substr($full_path, strlen($this->source)+1).PHP_EOL.file_get_contents($this->delList));
                }
                io::info("\t[~PURPLE~".$act."~~~] ".substr($full_path,strlen($this->source)), false);
                $r = $source->getFile($name)->copy($tf);
                if ($r) io::info();//('', IO::MESSAGE_OK);
                else io::out('throw Exception unable copy file '.$source->getFile($name).' to '.$tf, IO::MESSAGE_FAIL);
            }
            else if (is_dir($full_path)){
                $td = $target->getDir($name);

                if (!$td->exists()){
                    if (!$target->mkdir($name)) io::out('uNable create folder EXception', IO::MESSAGE_FAIL);
                    touch($this->delList);
                    file_put_contents($this->delList, substr($full_path, strlen($this->source)+1).PHP_EOL.file_get_contents($this->delList));
                }

                //IO::out('===> '.$name);
                $r = $this->copy($source->getDir($name), $target->getDir($name), $backup->getDir($name));
                if ($r);// io::out(' [ ~GREEN~OK~~~ ]');
                else io::out('throw Exception', IO::MESSAGE_FAIL);
                if (count( glob($backup->getDir($name).'/*')) == 0)$backup->getDir($name)->delete();
                //IO::out('  <=== '. $name);
            }
        }
        return true;
    }// }}}

}// }}}


// {{{ FileMover
/**
 *
 */
class FileMover{
    private $target;
    private $list = array();
    private $backupfile;

    function __construct($file, $target, $backupfile = null){
        $this->target = $target;
        $this->backupfile = $backupfile;

        if (file_exists($file))
            foreach(file($file) as $p) 
                if (preg_match('#^(\S+)\s+(\S+)$#', trim($p), $m) && count($m) == 3)
                    $this->list[$m[1]] = $m[2];
    }


    function rename(){
        if (!count($this->list)) return;
        foreach($this->list as $s => $t ){
            $source = $this->target->getFile($s);
            io::info("\t",false);
            if(!$source->exists()) {
                io::out('Source '.$source.' not found', IO::MESSAGE_WARN);
                continue;
            }
            
            io::info('[~PURPLE~M~~~] '.$s.' ==> '.$t);

            
            $r = rename($this->target->getFile($s), $this->target->getFile($t));
            if (!$r) IO::out('Throws exception', IO::MESSAGE_FAIL); 
                //t(new FileMover($this->backupfile, $this->target))->rename();
            elseif( !is_null($this->backupfile)) 
                    file_put_contents($this->backupfile, $t.' '.$s.PHP_EOL.($this->backupfile->exists()?file_get_contents($this->backupfile):''));
        }
    }
}// }}}


// {{{ FileDeleter
/**
 *
 */
class FileDeleter{
    private $target;
    private $backup;

    private $list = array();

    function __construct($file, $target, $backup=null){
        $this->target = $target;
        $this->backup = $backup;

        if (file_exists($file)) 
            foreach(file($file) as $p) $this->list[] = trim ($p);
    }

    public function del(){
        if (!count($this->list)) return;
        if (is_null($this->backup))
            foreach($this->list as $l) $olist[] = is_dir($this->target->getFile($l))?$this->target->getDir($l):$this->target->getFile($l);
        else  $olist = $this->backup();

            //io::out(substr($path, strlen($this->target))."\t" , false);
        if( count( $olist ) )  
            foreach($olist as $obj){
                io::info("\t[~PURPLE~D~~~] ".substr($obj, strlen($this->target))); 
                $obj->delete();
            }
    }

    // {{{ backup
    /**
     * Делает резервную копию файлов преде удалением
     *
     */
    private function backup(){
        $ol = array(); // list of backuped files and dirs
        foreach($this->list as $path){
            
            //io::out("\tBackup: ", false);
            // создание директории отката
            if(($parentDir = dirname($path)) != '.' && !$this->backup->getDir($parentDir)->exists()) 
                $this->backup->mkdir($parentDir);
            $df = $this->target->getDir($path);
            if (!$df->exists()){
                io::out($df.' not found', IO::MESSAGE_WARN);
                continue;
            }
            elseif (is_file($df)){ 

                $df = $this->target->getFile($path);
                $backupObject = $this->backup->getFile($path);
                //io::out ('[F] ', false);
            }
            else{ 
                $backupObject = $this->backup->getDir(dirname($path));
                //io::out('[D] ', false);
            }
            //io::out($path."\t" , false);

            $ol[] = $df; 
            try{
                $df->copy($backupObject);
            }catch(FileSystemException $e){
                return io::Out(PHP_EOL.'Backuping fail at '.$df.' With message '.$e->getMessage(), IO::MESSAGE_FAIL);

            }
            //IO::out(' ', IO::MESSAGE_OK);
        }
        return $ol;
    }// }}}

}// }}}
