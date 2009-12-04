<?php


class DeployerException extends CasseaException{}

class Deployer{
    private $name;
    private $workingDir;
    private $rollbackDir;
    private $taskList = array();

    //{{{ __construct
    /**
     *
     * @param $packageToDeploy директория с распакованным пакетом
     */
    function __construct(Dir $packageToDeploy){
        $this->workingDir = $packageToDeploy;
        $this->name =basename($this->workingDir);
        $this->rollbackDir = PackageManager::getRollbackDir()->getDir($this->name)->mkdir();
    }// }}}

    // {{{ setTaskList
    /**
     * Установка списка задач.
     *
     * Метод создает объекты задач, устанавливает директории отката и параметры задач.
     *
     * @param DOMNodeList $list
     */
    function setTaskList(DOMNodeList $list){
        if ( count($this->rollbackDir->ls()) )
            throw new DeployerException('RollbackDir('.$this->rollbackDir.') exists and not empty. Probably previously rollback fail.');

        Autoload::addDir(t(new File(__FILE__, true))->getParent()->getDir('deployTasks'));
        $this->addTasks($list);

    }// }}}

    // {{{ checkCondition
    /**
     * Проверяет условия указанные в теге condition.
     *
     * В данной момент реализована проверка установленного пакета
     *
     * @param DOMNode 
     * @return bool
     */
    private function checkCondition( DOMNode $node){
        IO::info('Check Condition ', false);
        $attr = $node->attributes;
        $packageName = is_null($attr->getNamedItem('package'))?null:$attr->getNamedItem('package')->nodeValue;
        if (!is_null($packageName)){
            $version = is_null($attr->getNamedItem('version'))?null:$attr->getNamedItem('version')->nodeValue;
            $rel = is_null($attr->getNamedItem('rel'))?null:Deps::normalizeRel($attr->getNamedItem('rel')->nodeValue);
            if (is_null($version) && $rel != '!='){
                $version = '0.0';
                $rel = '>=';
            }
            elseif(is_null($rel)) $rel = '=';
            io::info($packageName.' '.$rel.' '.$version."\t", false);
            $p = PackageManager::getInstalledPackage($packageName);
            if ($rel =='!=') $res = ($p === false);
            elseif($p !==  false) 
                $res = version_compare($p->version, $version, $rel);
            else $res = false;
            io::info('',$res?IO::MESSAGE_OK:IO::MESSAGE_FAIL);
            return $res;
        }
    }// }}}

    // {{{ addTasks
    /**
     * Добавляет задачи из списка $list
     *
     * @param DOMNodeList список задач
     */
    private function addTasks(DOMNodeList $list){
        foreach ($list as $task){
            // Skip chardata, comments
            if (strpos($task->nodeName, '#')!== false) continue;

            if ($task->nodeName != 'condition')  $this->addTask($task);
            elseif( $this->checkCondition($task) ) $this->addTasks($task->childNodes);
        }
    }// }}}

    private function addTask($task){
        //io::out($task->nodeName);
        $class = ucfirst($task->nodeName).'Task';
        $i = count($this->taskList)+1;
        $d = $this->rollbackDir->getDir($i)->mkdir();
        file_put_contents($d->getFile('.class'),$class);
        $this->taskList[$i] =  t(new $class($this->workingDir))->setRollbackDir($d)->setParam($task);
    }

    // {{{
    /**
     * Выполнение задач для развертывания пакета.
     *
     * Для каждой задачи из списка задач taskList вызвется метод deploy()
     *
     * @thorows Exception если одна из задач выбросит исключение
     */ 
    function executeTasks(){
        PackageManager::getRollback()->push('undeploy', $this->rollbackDir);
        $this->taskList = array_values($this->taskList);
        foreach ($this->taskList as $task){
            $task->deploy();
        }
    }// }}}

    // {{{ getPackageRollbackDir
    /**
     * Возвращаеет директорию для сохранения откатов для указанного пакета
     *
     * @param Package $p
     * @return Dir
     */
    static function getPackageRollbackDir(Package $p){
        return PackageManager::getRollbackDir()->getDir($p->name.'_'.$p->version);
    }// }}}

    // {{{ undeploy
    /**
     * Производит откат исходя из информации сохраненной в директории отката $dir
     *
     * В случае успешного отката возвоащает true, false - в случае если при откате
     * одной из задач было выброшено исключение 
     *
     * @param Dir $dir
     * @return bool
     */
    static function undeploy($dir){
        if (!$dir->exists())
            return io::out('Rollback dir('.$dir.') not exists.', IO::MESSAGE_WARN) | 2;
        $list = Dir::get($dir, true)->ls('*',Dir::LS_DIR);
        if (count($list) == 0 )
            return io::out('Undeploy list is empty');
        Autoload::addDir(t(new File(__FILE__, true))->getParent()->getDir('deployTasks'));
        $steps = array();
        foreach($list as $d)
            $steps[$d->getName()] = array(
                'class' => file_get_contents($d->getFile('.class')),
                'dir' => $d);
        krsort($steps);
        foreach($steps as $s){
            try{
                call_user_func($s['class'].'::undeploy',$s['dir']);
            }catch (Exception $e){
                io::out($e->getMessage(),IO::MESSAGE_FAIL);
                $fail = true;
            }
        }
        return isset($fail)?false:true;
    }// }}}
}

