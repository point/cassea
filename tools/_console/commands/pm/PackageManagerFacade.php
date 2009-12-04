<?php
Console::InitCore();
Autoload::addVendor('packageManager');

class PackageManagerFacade extends Command{

    // {{{ cmdUpdate
    /**
     * Обновляет информацию о пакетах в известных репозиториях.
     *
     * Прокси метод вызывающий pm repository update
     */
    function cmdUpdate(){
        require_once('commands/repository/RepositoryManager.php');
        $rm = new RepositoryManager('.', array('name'=>'repository', $this->commandsSeq));
        return $rm->cmdUpdate();

    }// }}}

    // {{{ cmdUpgrade 
    /**
     * Поиск и установка одновлений для всех пакетов
     */
    function cmdUpgrade(){
        PackageManager::get()->startup();
        IO::out('~WHITE~Upgrading packages:~~~');
        $upack = array();
        foreach( PackageManager::getPackageSequence() as $p) $upack[$p['name']] = 1;
        $upack = array_keys($upack);
        foreach($upack as $p){
            io::out('~GREEN~ >>> ~WHITE~'.$p.'~~~');
            try{
                PackageManager::install($p);
            }
            catch(Exception $e){
                io::out($p.': '.$e->getMessage(), IO::MESSAGE_FAIL);
            }
        }
        PackageManager::get()->shutdown();
    }// }}}

    // {{{ cmdList
    /**
     * Список установленных пакетов,
     * список репозиториев 
     */
    function cmdList(){
        PackageManager::get()->startup();
        $l = PackageManager::getPackageSequence();
        if (!count($l)) io::out('Packages not installed');
        else{
            io::out('~WHITE~Installed packages:~~~');
            io::out(sprintf('~SILVER~%-15s %-10s %s~~~','Package Name', 'Version', 'Summary'));
            foreach($l as $p)
                io::out(sprintf('%-15s %-10s %s', $p['name'],$p['version'], PackageManager::getInstalledPackage($p['name'], $p['version'])->summary));
        }
        PackageManager::get()->shutdown();
    } // }}}

    // {{{ cmdInfo 
    /**
     * Информация об указанном пакете 
     */
    function cmdInfo(){
        if ( ($package = ArgsHolder::get()->shiftCommand(false)) === false)
            return IO::out('Specify package file', IO::MESSAGE_FAIL) | 1;
        if ( ($version = ArgsHolder::get()->shiftCommand()) === false)
            $version  = null;
        try{
            PackageManager::get()->startup();
            $width = 20;

            $p = PackageManager::getInstalledPackage(trim($package,'\'"'), $version);
            if ($p !== false){
                $r = array('Name', 'Version', 'Summary', 'Mantainer', 'Tags');

                foreach($r as $k){
                    $pname = strtolower($k);
                    io::out(sprintf('%18s: %s', $k, $p->$pname));
                }

                // description
                $str = '';
                if (trim( $p->description ) != ''){
                    foreach(explode(PHP_EOL.PHP_EOL, trim( $p->description )) as $aa)
                        $str .= chunk_split(implode(' ', explode(PHP_EOL,$aa))).PHP_EOL;

                    $a = explode(PHP_EOL,$str);
                    io::out(sprintf('%18s: %s','Description', $a[0]));
                    for ($i = 1, $c = count($a); $i < $c; $i++ )
                        if(trim($a[$i]) != '') io::out(sprintf('%'.($i?$width:'').'s%s','',$a[$i] ));
                } 
                // deps
                $d= $p->deps;
                if (!is_null($php = $d['php'])) $as  = 'PHP '.$php['rel'].' '.$php['version'];
                if (count($ex = $d['phpExtensions'])) $as.= '; '. implode(', ', $ex);
                $a = isset($as)?array($as):array();
                if (count($inis = $d['phpIniValues'])) foreach($inis as $i) $a[] = 'ini.'.$i['name'].' '.$i['rel'].' '.$i['value'];
                if (count($pkgs = $d['packages'])) foreach($pkgs as $i) $a[] = ''.$i['name'].' '.$i['rel'].' '.$i['version'];

                io::out(sprintf('%18s: %s','Dependensies', count($a)?$a[0]:''));
                for ($i = 1, $c = count($a); $i < $c; $i++ )
                    io::out(sprintf('%'.($i?$width:'').'s%s','',$a[$i] ));
            } 
            else io::out('Given package '.$package.(!is_null($version)?"($version)":'').' not installed', IO::MESSAGE_FAIL);
            PackageManager::get()->shutdown();
           
        }catch(Exception $e){
            PackageManager::getRollback()->stepBack();
            return io::out($e->getMessage(), IO::MESSAGE_FAIL)| (is_null($e->getCode())?2:$e->getCode());
        }

    }// }}}

    // {{{  cmdInstall
    /**
     * Установка указанного пакета
     */
    function cmdInstall(){
        if ( ($package = ArgsHolder::get()->shiftCommand(false)) == false)
            return IO::out('Specify package file or package name', IO::MESSAGE_FAIL) | 1;
        try{
            PackageManager::get()->startup();

            PackageManager::install(trim($package,'\'"'));
            
            PackageManager::get()->shutdown();
           
        }catch(Exception $e){
            PackageManager::getRollback()->stepBack();
            PackageManager::get()->shutdown();

            return io::out($e->getMessage(), IO::MESSAGE_FAIL)| (is_null($e->getCode())?2:$e->getCode());
        }
    }// }}}

    // {{{cmdUninstall
    /**
     * Удаление указанного пакета
     */
    function cmdUninstall(){
        if ( ($nvr = ArgsHolder::get()->shiftCommand(false)) == false)
            return IO::out('Specify package file', IO::MESSAGE_FAIL) | 1;
        try{
            PackageManager::get()->startup();

            PackageManager::uninstall(trim($nvr,'\'"'));
            
            PackageManager::get()->shutdown();
           
        }catch(Exception $e){
            PackageManager::getRollback()->stepBack();
            return io::out($e->getMessage(), IO::MESSAGE_FAIL)| (is_null($e->getCode())?2:$e->getCode());
        }
    }// }}}

    // {{{ cmdUnlock
    /**
     * Снимает блокировку системы.
     *
     */
    function cmdUnlock(){
        IO::out('Unlocking Package Manager',false);
        if (PackageManager::get()->unlock(true)) io::done();
    }// }}}
}
