<?php
Console::InitCore();
Autoload::addDir(dirname(__FILE__));

class PackageManagerFacade extends Command{
    private $dataDir;

    // {{{ __construct
    /**
     * Конструктор классы.
     *
     * Устанавливает значения переменных класса.
     *
     * @param string $workingDir рабочая директория комманды;
     * @param array $info массив с именем комманды и коммандо по умолчанию;
     * @param array $commandsSeq  массив с предшествующими коммандами;
     */  
    public function __construct( $workingDir = '.', $info, $commandsSeq = array()){
        parent::__construct( $workingDir, $info, $commandsSeq );
        $conf = Config::getInstance();
        $this->dataDir = new Dir($conf->root_dir.$conf->data_dir.'/packages', true);
    }// }}}


    //
    static function getDataDir(){
        $conf = Config::getInstance();
        return Dir($conf->root_dir.$conf->data_dir.'/packages', true);
    }

    function cmdInstall(){

    
    }

    function cmdRemove(){

    }

    function cmdRepo(){
        $file = new File('/home/billy/1.tar.bz2', true);
        $target = new Dir('/home/billy/temp/1.unpack/includes', true);
        //Packer::unpack($file, $target);
        Packer::pack($target, new File(dirname(__FILE__).'/2.tbz', true));

        return;


        $pm = new PM();
        $pm->startup();
        $rb = PM::getRollback();
        $rb->push('delete', dirname(__FILE__).'/_files/source', dirname(__FILE__).'/_files/target/includes/Controller.php' );
        //$rb->push('delete', './_files/source', '_files/target/includes/Controller.php' );
        //$r = $rb->pop();
        $r = $rb->stepBack();
        print_r($r);


        return;
        $ps = PM::getPackageSequence();


        print_r($ps->get());
        print_r($ps->getAfter('news', '2.3'));
        //print_pre($ps->addPackage('news', '2.8'));
        print_pre($ps->removePackage('news', '2.8'));

        return;
        Autoload::addDir(Dir::get($this->root, true)->getDir('repo'));

        $rl = new RepositoryList($this->dataDir->getFile('source.list'));
        $r = $rl->search(explode(' ','qt package'));

        print_pre($r);
        $pm->shutdown();
    }

    function cmdUnlock(){
        IO::out('Unlocking Package Manager',false);
        $pm = new PM();
        if ($pm->unlock(true)) io::done();
    }




    function FileUpadter(){

        FileSystemObject::setRoot(dirname(__FILE__));
        $d = new Dir('files');


        $fu = new FileUpdater($d->getDir('source/includes'), $d->getDir('target/includes'), $d->getDir('backup/includes'));
            
        $command =ArgsHolder::get()->shiftCommand(); 

        if ($command == 'cdo'){
            $fu->copyDo();
        }
        elseif ($command =='cundo'){
            $fu->copyUndo();
        }
        elseif ($command =='undo'){
            $fu->undo();
        }
        elseif( $command == 'do') $fu->run();
        else io::out('command not found');

    }


}    

/*
require_once('Repository.php');
function &t (&$obj){return $obj; }

$sl = new RepositoryList('source.list');

$repo = $sl->getRepository('file:///home/billy/work/packages/repo1');

print_r($repo);


die();
print_r($sl->getList());

//var_dump($sl->addRepository('file:///home/billy/work/packages/repo1'));
//var_dump($sl->add('source1'));
//var_dump($sl->add('source3'));
#var_dump($sl->delete('source1'));

print_r($sl->getList());

foreach($sl->getList() as $s) echo ">> ".$s." ".($sl->verify($s)?"OK":'FAIL').PHP_EOL;

 */
