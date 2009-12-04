<?php
/**
 * TODO zippedQueries для больших файлов
 *
 *
 *
 */
class SqlTask extends DeployTask{

    // {{{ deploy
    /**
     * Выполнение дейтвий для развертывания задачи.
     *
     * Сперва соханяется откат(если  он указан в теге),
     * после чего выполняется файл с sql запросами указаныый в аттрибуте 'queries'.
     *
     * @throws SqlTaskException
     */
    function deploy(){
        $attr = $this->node->attributes;
        if (!is_null($r = $attr->getNamedItem('rollback')))
            $this->storeRollback($r->nodeValue);

        if (!is_null($r = $attr->getNamedItem('queries')))
            $this->processQueries($r->nodeValue);
    }// }}}

    // {{{ processQueries
    /**
     * Читает содержимое файла и выполняет его.
     *
     * @throw SqlTaskException
     * @param string $filename значение аттрибуту queries
     */
    private function processQueries($filename){
        $f = $this->getFile($filename);
        io::out("\t[~PURPLE~Q~~~] ".basename($f), false);
        SqlTask::execQuery(file_get_contents($f)); 
        io::done();
    }// }}}

    // {{{ execQuery
    /**
     * Выполняет запросы переданные в строке $sql.
     *
     * @throws SqlTaskException если при выполнение запросов произошла ошибка
     * @param string $sql
     */
    static private function execQuery($sql){
        try{
            DB::multiQuery($sql);
        }catch(DBException $e){
            throw new SqlTaskException('Mysql Error:'.PHP_EOL.
                    'Query: '.$e->getQuery().PHP_EOL.
                    'Error: '.$e->getMessage().PHP_EOL.
                    'Code : '.$e->getCOde().PHP_EOL);
        }
    }// }}}
    
    // {{{ storeRollback
    /**
     * Сохраняет в папке отката файл с запросами необходимыми для отката (имя файла указанное в аттрибуте rollback)
     *
     * @throws SqlTaskException
     * @param string $filename 
     */
    private function storeRollback($filename){
        $this->getFile($filename)->copy($this->rollbackDir->getFile('queries.sql'));
    }// }}}

    // {{{ getFile
    /**
     * Возвращает файл объекта по имени указанном в аттрибуте queries
     *
     * @throws SqlTaskException если файл не доступен для чтения
     * @param string $filename
     * @return File
     */
    private function getFile($filename){
        $filename = $filename.(substr($filename, -4) != '.sql'?'.sql':'');
        $file =  $this->dir->getDir('sql')->getFile($filename);
        if (!$file->canRead() )
            throw new SqlTaskException('File "'.$filename.'('.$file.')" is not readable');
        return  $file;
    }// }}}

    // {{{ undeploy
    static function undeploy($dir){
        $f = $dir->getFile('queries.sql');
        // файла нет или он пустой
        if (!$f->canRead() || ($queries = file_get_contents($f))=== false) return;
        io::out("\t".'[~PURPLE~Q~~~] '.basename($f->getParent()).'/'.basename($f).'~~~', false);
        SqlTask::execQuery($queries);
        io::done();
    }// }}}
}
