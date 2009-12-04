<?php

class PackageManagerException extends CasseaException{}

class PackageManager{
    private $dataDir;
    /**
     * Файл блокировки
     * @var CFile
     */
    private $lockFile;


    private $packagesSequence = null;
    private $rollback = null;


    private $options = array(
        'with_deps' => true,
        'ignore_deps' => false
    );
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
    private function __construct(){
        $conf = Config::getInstance();
        $this->dataDir = new Dir($conf->root_dir.$conf->data_dir.'/packages', true);
        $this->lockFile = $this->dataDir->getFile('lock');

        $this->packagesSequence = new PackagesSequence( $this->dataDir->getFile('packagesSequence.txt'));
        $this->rollback = new RollBack( $this->dataDir->getFile('rollback.txt'));
        $this->repositoryList = new RepositoryList($this->dataDir->getDir('repositories'));
    }// }}}

    // {{{ get
    /**
     *
     */
    static function get(){
        static $singletone = null;
        if (!is_object($singletone)) $singletone = new PackageManager();
        return $singletone;
     }//}}}
    
    // {{{ startup
    /**
     * Инициализация 
     */
    function startup(){
        // блокировки
        // TODO обработка ключей
        if ($this->isLock())
            throw new PackageManagerException('PackageSystem is Locked with info: '.file_get_contents($this->lockFile));
        $this->lock();

        //Если есть назавершенный откат пытаемся откатится
        // TODO Вставить обработку ключей
        // --ignore_rollback
        // --flush_rollback
        //io::out('Checking rollback', false);

        if ($this->rollback->count()) {
            if( IO::YES == io::dialog(PHP_EOL.'Have unfinished rollback.Continue  it?', IO::YES|IO::NO, IO::YES))
                $this->rollback->stepBack();
        }
        //else  io::done();
        return $this;
    }// }}}


    // {{{ shutdown
    /**
     * Завершение работы
     *
     */
    function shutdown(){
        static $shutdowned = false;
        if ($shutdowned) return;
        $this->rollback->clean();
        if ($this->isLock()){ if (!$this->unlock()) throw new PackageManagerException('Error while unlocking.');}
        else io::out('System not lockeed. Очень странно.');

        $shutdowned = true;
    }// }}}

    // {{{ getter functions
    static function getDataDir(){
        return PackageManager::get()->dataDir;
    }

    static function getInstalledDir(){
        return PackageManager::get()->dataDir->getDir('installed');
    }

    static function getDownloadDir(){
        return PackageManager::get()->dataDir->getDir('downloaded');
    }

    static function getBuildpadDir(){
        return PackageManager::get()->dataDir->getDir('buildpad');
    }

    static function getRollbackDir(){
        return PackageManager::get()->dataDir->getDir('rollback');
    }

    static function getPackageSequence(){
        return PackageManager::get()->packagesSequence->get();
    }

    static function getRollback(){
        return PackageManager::get()->rollback;
    }

    static function getRepositoryList(){
        return PackageManager::get()->repositoryList;
    }// }}}


    // {{{ getInstalledPackage
    /**
     * Возвращает объект установленного пакета.
     *
     * @param string name
     * @return Package or false if packages not installed
     */
    static function getInstalledPackage($name, $version = null){
        if (is_null($version))
            foreach( array_reverse(PackageManager::getPackageSequence()) as $p )
                if($p['name'] == $name) { $version = $p['version']; break;}

        if (is_null($version)) return false;
        
        $iFile = PackageManager::getInstalledDir()->getFile($name.'_'.$version.'.tbz');
        return Package::isPackage($iFile);
    }// }}}

    // {{{ parseNRV
    /**
     * Преобразовывает строку зависимости (например news>=3.2)
     * в массив с компонентами name, version, rel.
     *
     * Если в строке не указанна версия и отношение "news" 
     * это равносильно news>=0.0.
     *
     * @param string
     * @return array 
     */
    static function parseNRV($string){
        if (preg_match('#^(.+?)(([=<>_]{1,2})(\d+\.\d+))?$#', trim($string), $m)){
            $name = $m[1];
            $version = isset($m[4])?$m[4]:null;
            $rel = isset($m[3])?$m[3]:null;
            if (is_null($version)){
                $version = '0.0';
                $rel = '>=';
            }

            return  array('name' => $name, 'version' =>$version, 'rel' => $rel); 
        }
        throw new PackageManagerException('Cant parse "Name Rel Version" string ('.$string.').');
    }// }}}

    // {{{ install
    /**
     * Инсталяция пакета
     *
     * Пакет $package может быть представлен ввиде локального фала(~/mypackage.tbz),
     * имени пакета (admin) или ввиде имени пакет, версии и отношения (news>=2.0-alpha)
     *
     * @throw PackageManagerException, RepositoryListException
     * @param string $package
     */
    static function install($package){
        // локальный файл
        if (($fp = realpath($package)) !== false)
            if ( ($p = Package::isPackage(new File($fp, true))) !== false ) $package = $p;
            else throw new PackageManagerException('Given file ('.$package.') isn\'t a well formed  package.');

        // зависимости
        io::out('~WHITE~Checking dependencies~~~');
        $installList = Deps::calculate($package);
        if( $installList === false )
            throw new PackageManagerException('Невозможно удовлетворить зависимости, или не найдены требуемые пакеты.');
        elseif ($installList instanceof Package)
            return  IO::out('Установленная версия пакета '.$installList->name.': '.$installList->version) | 0;


        $newPackages = array();
        $updatePackages = array();
        $installedPackages = array();
        $list = array(); 
        for($i =0, $c = count($installList); $i < $c; $i++){
            $package = $installList[$i];
            
            if ($package->status != Package::INSTALLED) $list[] = $package;

            // for information oupput
            if ($package->status == Package::INSTALLED) $installedPackages[] = $package;
            else{
                if (false === ($p = PackageManager::getInstalledPackage($package->name))) $newPackages[] = $package;
                else $updatePackages[] = $p;
            }
        }

        
        foreach($newPackages as $p) $n[] = $p->name.'('.$p->version.')';
        if (isset($n))  io::out('New Packages: '.implode(', ',$n));

        foreach($updatePackages as $p) $u[] = $p->name.'('.$p->version.')';
        if (isset($u))  io::out('Packages to be updated: '.implode(', ',$u));

        foreach($installedPackages as $p) $inst[] = $p->name.'('.$p->version.')';
        if (isset($inst))  io::out('Installed Packages: '.implode(', ',$inst));




        // доставка
        $deployList = array();
        io::out('~WHITE~Fetching packages~~~');
        $dDir = self::getDownloadDir();
        foreach($list as $package){
            io::out($package->name.'_'.$package->version.': ', false);
            $tFile = $dDir->getFile($package->name.'_'.$package->version.'.tbz');
            //look in downloaded dir
            $p = Package::isPackage($tFile);
            if ($p instanceof Package && $p->name == $package->name && $p->version == $package->version)
            {
                io::out('Already downloaded', false);
                $deployList[] =  $p;
            } 
            else{
                // откат для скачанногофайла
                PackageManager::getRollback()->push('delete', $tFile);
                $deployList[]= $package->delivery($tFile);
            }
            io::done();
        }

        try{
            foreach($deployList as $p){
                io::out('~WHITE~Deploying '.$p->name.'('.$p->version.'):~~~');
                $p->deploy();
                $p->file->move(PackageManager::getInstalledDir()->getFile($p->name.'_'.$p->version.'.tbz'));
                PackageManager::get()->packagesSequence->addPackage($p->name, $p->version);
            }
        }catch (Exception $e){
            echo $e->getMessage();
            PackageManager::getRollback()->stepBack();
        }

        return;
    }// }}}

    // {{{ uninstall
    /**
     * Удаление пакета
     */
    static function uninstall($package){
        $nvr = PackageManager::parseNRV($package);

        // проверка обратных зависимостей
        $res = Deps::isNoNeeded($nvr, $unstatisfied);
        if (count($res) == 0 ) return IO::out('Packages to uninstall not found', IO::MESSAGE_FAIL);
        if ($res === false){
            io::out('Unable uninstall package ~WHITE~'.$nvr['name'].'~~~ because', IO::MESSAGE_FAIL);
            foreach($unstatisfied as $p => $d)
                io::out('Package '.$p.' require  '.implode(', ',array_keys($d)));
            return false;
        }
        
        io::out('Packages to be removed: ',false );
        foreach($res as $p) io::out($p->name.'('.$p->version.') ', false );
        io::out();
        if (IO::NO == io::dialog('Continue?', IO::YES | IO::NO, IO::NO)) return 1; 
        foreach($res as $p){
            io::out('~WHITE~Undeploying '.$p->name.'('.$p->version.'):~~~' );
            $r = Deployer::undeploy(Deployer::getPackageRollbackDir($p));
            if ($r || IO::OK != IO::dialog('Some part of rollback failed. Remove anyway?', IO::YES | IO::NO, IO::YES)){
                Deployer::getPackageRollbackDir($p)->delete();
                $p->file->delete();
                PackageManager::get()->packagesSequence->removePackage($p->name, $p->version);
            }
        } 
    }// }}}
    
    // {{{ Locking
    // {{{ lock
    /**
     * Блокирует менеджер пакетов
     *
     * @return bool true в случае успешной блокировки
     */
    private function lock(){
        if ($this->isLock()) return false;
        return file_put_contents($this->lockFile, get_current_user().' '.date('M d Y H:i:s' ));
    }// }}}

    // {{{ unlock
    /**
     * Снятие блокировки
     *
     * @param bool $force 
     * @return bool true если блокировка снята и была утановленна 
     */
    function unlock($force = false){
        if (!$force && !$this->isLock()) return false;
        if(!file_exists($this->lockFile)) return true;
        return unlink($this->lockFile);
    }// }}}

    // {{{ isLock
    /**
     * Проверка блокировки
     *
     * @return bool true если систем заблокирована
     */
    private function isLock(){
        return file_exists($this->lockFile);
    }// }}}

    // }}} 
}

