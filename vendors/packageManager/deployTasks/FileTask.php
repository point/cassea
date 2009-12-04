<?php

/**
 *
 *
 *
 */
class FileTask extends DeployTask{
    private $source;
    private $target;
    private $sourcesDir;

    // {{{ __construct
    /**
     * Конструктор
     *
     * @param Dir $buildpad
     */
    function __construct($buildpad){
        parent::__construct($buildpad);
        $this->sourcesDir = $this->dir->getDir('files');
    }// }}}

    // {{{ deploy
    /**
     * Выполнение дейтвий для развертывания задачи.
     *
     * @throws FileTaskException
     */
    function deploy(){
        $attr = $this->node->attributes;
        if (!is_null($source = $attr->getNamedItem('source'))){
            $this->source = $this->sourcesDir->getDir($source->nodeValue);
            if (!$this->source->exists()) return io::out('Source direcotry '.$this->source.' not exists', IO::MESSAGE_WARN)| 1;

            $target = is_null($t = $attr->getNamedItem('target'))?$source->nodeValue:$t->nodeValue;

            if (!is_null($vendor = $attr->getNamedItem('vendor')) && $vendor->nodeValue == '0' ) $vendor = false;
            else $vendor = true;
            
            $this->target = $this->getTargetDir($target, $vendor);
            io::out('Deploying ~BLUE~'.$source->nodeValue.'~~~ => '.
                '~GRAY~'.(substr($this->target,strlen(Config::getInstance()->root_dir))).' ~~~: ');
            $this->deployFolder();
        }
    }// }}}

    // {{{ getTargetDir
    /**
     * Получение целевой директории исходя из имени источника
     *
     */
    private function getTargetDir($targetName, $vendor){
        static $targetList;
        if (!is_array($targetList)){
            $c = Config::getInstance();
            $rd = Dir::get($c->root_dir, true);
            $targetList = array(
                'data' => $rd->getDir($c->data_dir),
                'docs' => $rd->getDir('docs'),
                'includes' => $rd->getDir('includes'),
                'widgets' => $rd->getDir('includes/widgets'),
                'pages' => $rd->getDir($c->xmlpages_dir),
                'models' => $rd->getDir($c->models_dir),
                'root' => $rd,
                'vendors' => $rd->getDir($c->vendors_dir),
                'vendors_includes'=> $rd->getDir($c->vendors_dir.'/includes/'),
                'vendors_widgets' => $rd->getDir($c->vendors_dir.'/widgets'),
                'vendors_pages' => $rd->getDir($c->vendors_dir)->getDir($c->xmlpages_dir),
                'vendors_plugins' =>$rd->getDir($c->vendors_dir.'/plugins/'),
                'console_command' => $rd->getDir('tools/_console/commands/')
                );
        }
        if ($vendor && isset( $targetList['vendors_'.$targetName])) return $targetList['vendors_'.$targetName];
        elseif (isset($targetList[$targetName]) ) return $targetList[$targetName];
        else throw new FileTaskException('Can\'t resolve target: targe =  '.$targetName.' vendorsFlag '.($vendor?'set':'no tset'));
    }// }}}


    // {{{ storeTargetPath
    /**
     * В файле .fuTarget сохараняется целевой каталог(в который развернулась задача)
     *
     */
    private function storeTargetPath(){
        if (file_put_contents( ($f=$this->rollbackDir->getFile('.fuTarget')), $this->target)=== false)
           throw FileTaskException('Unable store rollback information(paths) in '.$f); 
    }// }}}

    // {{{ restoreTargerPath
    /**
     * Восстанавливает пути для создания FileUpdater'a
     *
     * @param Dir директория отката
     * @return array of Dir
     */
    static private function restoreTargetPath($dir){
        if (false !==  ($c = file_get_contents($dir->getFile('.fuTarget')))) return Dir::get(trim($c), true);
        throw new FileTaskException('Unable to restore Target folder of task.');
    }// }}}

    // {{{ deployFolder
    /**
     *
     */
    private function deployFolder(){
        // сохранение путей для повторного
        $this->storeTargetPath();

        $fu = new FileUpdater($this->source, $this->target, $this->rollbackDir);
        $fu->run();
        
        
    }// }}}

    // {{{ undeploy
    static function undeploy($dir){
        io::out('Undeploying => ~GRAY~'.(substr($dir, strlen(Config::getInstance()->root_dir))).'~~~: ');
        $fu = new FileUpdater(Dir::get(Config::getInstance()->root_dir, true), self::restoreTargetPath($dir),$dir);
        $fu->undo();
    }// }}}
}
