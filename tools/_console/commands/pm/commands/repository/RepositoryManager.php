<?php
Console::InitCore();
Autoload::addVendor('packageManager');

class RepositoryManagerException extends CasseaException{}

class RepositoryManager  extends Command{
    private $rl;

    public function __construct( $workingDir = '.', $info, $commandsSeq = array())
    {
        parent::__construct( $workingDir, $info, $commandsSeq);
        $this->rl = new RepositoryList(PackageManager::getDataDir()->getDir('repositories'));
    }

    function cmdAdd(){
        if ( ($url = ArgsHolder::get()->shiftCommand()) == false)
            return IO::out('Specify repository URL', IO::MESSAGE_FAIL) | 1;

        @$p = parse_url($url);
        if ($p === false || !isset($p['host']))
            return IO::out('Specify correct repository URL ', IO::MESSAGE_FAIL) | 1;

        try{
            if ( $this->rl->addRepository($url))
                io::out('Repository ~WHITE~'.$url.'~~~ added',IO::MESSAGE_OK); 
            else
                io::out('Repository ~WHITE~'.$url.'~~~ already in list',IO::MESSAGE_WARN); 
        }
        catch (RepositoryListException $e){
            return io::out($e->getMessage(), IO::MESSAGE_FAIL)| 2;
        }
    }


    function cmdDelete(){
        if ( ($url = ArgsHolder::get()->shiftCommand()) == false)
            return IO::out('Specify repository URL', IO::MESSAGE_FAIL) | 1;

        @$p = parse_url($url);
        if ($p === false || !isset($p['host']))
            return IO::out('Specify correct repository URL ', IO::MESSAGE_FAIL) | 1;

        try{
            if ( $this->rl->deleteRepository($url))
                io::out('Repository ~WHITE~'.$url.'~~~ removed',IO::MESSAGE_OK); 
            else
                io::out('Repository ~WHITE~'.$url.'~~~ not in list',IO::MESSAGE_WARN); 
        }
        catch (RepositoryListException $e){
            return io::out($e->getMessage(), IO::MESSAGE_FAIL)| 2;
        }

    }
    function cmdList(){
        $list = $this->rl->getList();
        if (!count($list)) return IO::out('Repository List empty.');
        io::out('~WHITE~List of  Repositories~~~:' );
        for($i = 0, $c = count($list); $i< $c; $i++)
            io::out($list[$i]);
        io::out();
        io::out('Total: '.$c);
    }
        
    function cmdUpdate(){
        io::out('~WHITE~Updating repositories:~~~');
        $list = array(); $width = 0;
        if ( ($url = ArgsHolder::get()->shiftCommand()) != false) $list = array($url);
        else $list =  $this->rl->getList();
        if (!count($list )) return io::out('Repository list empty', IO::MESSAGE_WARN) | 2;

        foreach($list as $url) $width = max($width, strlen($url));

        foreach( $this->rl->getList() as $url){   
            io::out(sprintf('%-'.($width+2).'s',$url), false);
            try{
                $r = $this->rl->updateRepository($url);
                if ($r === false) io::out('[ ~GRAY~not modified~~~ ]');
                else io::out('[ '.sprintf('%2u',$r).' packages ]');
            }catch(RepositoryListException $e ){
                io::out($e->getMessage(), IO::MESSAGE_FAIL);
            }
        }
    }

    function cmdFlush(){
        io::out('Flushing repository information...', false);
        $this->rl->flushRepositories();
        io::done();
    }

}

