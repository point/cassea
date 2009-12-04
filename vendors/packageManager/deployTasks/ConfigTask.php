<?php
/**
 *
 *
 */
class ConfigTask extends DeployTask{
    private $file;
    private $pattern;
    private $lines;
    private $k2l;
    private $rowSection = array(); // delete


    // {{{ getPattern
    /**
     * Разбирает файл или массив строк методом parse_ini_file;
     *
     * Проверряет существование файла, парстит его методом parse_ini и нормализует
     * имена сеций([ myConfig: base] => [myConfig])
     *
     * Если в качестве аргумента передан масси, то он сохранятся во временный файл.
     * 
     * TODO PHP5.3 parse_ini_string
     *
     * @param iFile | arrray
     * @return array
     */
    function getPattern($file){
        if ($file instanceof iFile && !$file->exists())
            throw new ConfigTaskException('File '.$file.' not found');
        elseif (is_array($file)){
            $tFile = new TempFile();
            file_put_contents($tFile, implode('', $file));
            $file = $tFile;  
        }
        @$arr = parse_ini_file($file, true);
        if ($arr === false) throw new Exception('Error while parsing ini-file '.$file);
        foreach($arr as $sect => $c){
            if (($p = strPos($sect, Config::getInstance()->getInheritSeparator())) !== false ) $sect2 = trim(substr($sect,0, $p));
            $pattern[isset($sect2)?$sect2:$sect] = $c;
        }
        return $pattern;
    }// }}}

    // {{{ rescanLines
    /**
     *
     */
    function rescanLines($lines){
        $tFile = new TempFile();
        file_put_contents($tFile, implode('', $lines));
        $lines2 = file($tFile);
        //io::out('Rescan Lines: '.count($lines).' => '.count($lines2));
        //print_r($lines2);
        //io::out('====================================================');
        return $lines2;  
    }// }}}

    // {{{ processLines
    function processLines($lines, &$k2l){
        $new = array();
        $curSect = null;
        $prevLine = null;
        foreach($lines as $line){
            if (!is_null($prevLine)) $line = $prevLine = $prevLine.$line;
            if ($this->getSectionName($line, $curSect)) $k2l[$curSect][''] = count($new);// echo "     SECT:  ".$curSect.PHP_EOL;
            elseif ($this->getKey($line, $key, $val)){
                if (substr($val,0,1) == '"')
                    if (substr($val,-1) == '"'){ 
                        $val = substr($val,1,-1);
                        $prevLine = null;
                    }
                    elseif(is_null($prevLine)) $prevLine = $line;

                if (is_null($prevLine)){
                    //echo '['.$curSect.'] '.$key.' = '.$val.PHP_EOL;
                    $k2l[$curSect][$key] = count($new);
                }
            }
            if (is_null($prevLine) && !is_null($line)) $new[] = $line;
        }
        return $new;
    }// }}}

    // {{{ getSectionName
    private function getSectionName($str,&$name){
        $str = trim($str);
        if (substr($str,0,1) != '[')  return false;
        if (($p = strpos($str, ']'))=== false ) return false;
        $sectName =(substr($str, 1, $p-1));
        $a = explode(':',trim($sectName));
        $name = trim($a[0]);
        $this->rawSection[$name] = $sectName;
        return true;
    }// }}}

    // {{{ getKey
    /**
     *
     * TODO replace by parse_ini_string in PHP5.3
     */
    private function getKey($str, &$key, &$val){
        $str = trim($str);
        // remove comment
        if (($s = substr($str, 0,1)) == ';' || $s == '#')return false;

        //io::out('==>'.$str.'<==');
        //if(preg_match('/([a-zA-Z._0-9]+)\s*=\s*(\S+)\s*$/', $str, $m))
        //if(preg_match('/([a-zA-Z._0-9]+)\s*=\s*(\S+)/', $str, $m))
        //    print_pre($m);

        if (preg_match('/((\w|[._]|\d)+)\s*=\s*("[^"]*")\s*$/m', $str, $m) ||
            preg_match('/((\w|[._]|\d)+)\s*=\s*(.*)$/', $str, $m)){

            $key = $m[1];
            $val = trim($m[3]);

            return true;
            }
        return false;
    }// }}}

    //{{{ doOperations
    private function doOperations($list){
        $i = 0;
        foreach($list as $op){
            $method = $op['operation'];
            $param = $op['param'];
            $this->$method($param);
            file_put_contents($this->rollbackDir->getFile($i++.'_'.$method.'.ini'), implode('',$this->lines));
        }
    }// }}}

    // {{{ deploy
    /**
     * Выполнение дейтвий для развертывания задачи.
     *
     */
    function deploy(){
        $operations = $this->parseNode();
        io::out('Updating config ~BLUE~'.basename($this->file).'~~~:');
        $this->pattern = $this->getPattern($this->file);
        $this->lines = $this->processLines(file($this->file), $this->k2l);
        $this->file->copy($this->rollbackDir->getFile('original.ini'));
        $this->doOperations($operations);
        $content = implode('', $this->lines);
        io::out('Write down ~BLUE~'.basename($this->file).'~~~ ('.$this->file.")\t", false); 
        $a = file_put_contents($this->file, $content);
        if ($a !== false) io::out( '[ '.$a.' bytes ]', IO::MESSAGE_OK);
        else {
            file_put_contents($this->file.'.new', $content);
            throw new ConfigTaskException('Error while writing back config '.$file);
        }

    }// }}}

    // {{{ undeploy
    static function undeploy($dir){
        $o = new ConfigTask();
        $file = file_get_contents($dir->getFile('config.txt'));
        io::out('Undeploying changes ~BLUE~'.basename($file).'~~~:');
        $o->file = new File($file, true);
        $o->pattern = $o->getPattern($o->file);
        $o->lines = $o->processLines(file($o->file), $o->k2l);
        $opfile = $dir->getFile('operations.txt');
        if (!$opfile->exists()) return;

        $op = unserialize(file_get_contents($opfile));
        for($i = count($op)-1; $i >= 0; $i--){
            $operation = $op[$i]['operation'];
            $o->$operation($op[$i]['param']); 
        }
        $content = implode('', $o->lines);
        io::out('Write down ~BLUE~'.basename($o->file).'~~~ ('.$o->file.")\t", false); 
        $a = file_put_contents($o->file, $content);
        if ($a !== false) io::out( $a.' bytes', IO::MESSAGE_OK);
        else {
            file_put_contents($o->file.'.new', $content);
            throw new ConfigTaskException('Error while writing back config '.$file);
        }
    }// }}}

    // {{{ create
    private function create($param){
        if (!isset($param['key'])) throw new ConfigTaskException('Key name not set in "create" operation. File('.basename($this->file).')');
        $key = $param['key'];
        if (isset($param['value'])) $value= $param['value'];
        elseif(isset($param['textValue'])) $value = $param['textValue'];
        else $value = "''";
        $section = isset($param['section'])?$param['section']:'base';


        // секции не существует
        if (!isset($this->k2l[$section])){ 
            io::info("\t[~PURPLE~A~~~] [".$section."] ".$key.' = '.$value);
            throw new ConfigTaskException('Section "'.$section.'" not found');
        }
        // ключ существует
        if (isset($this->pattern[$section][$key])) return $this->update($param);

        io::info("\t[~PURPLE~A~~~] [".$section."] ".$key.' = '.$value);
        $this->addRollbackOperation('delete', array('key' => $key, 'section' => $section) );

        // номер строки в массиве lines
        $s = $this->k2l[$section];
        $line = array_pop($s );// last string in target section
        $lines = $this->lines; // local copy 
        $lines[ $line] .= $key.' = '.$value.PHP_EOL;

        $newini = $this->getPattern($lines);
        $diff = self::diffArray($newini, $this->pattern);
        if ($diff['del'] != 0 || $diff['add'] != 1 || $diff['modify'] != 0) // check count of modifications 
            throw new ConfigTaskException('Error while creating constant. Given diff: '.implode('-', $diff));

        $newK2l = array();
        $this->lines = $this->processLines($this->rescanLines($lines), $newK2l);
        $this->pattern = $newini;
        $this->k2l = $newK2l;
    }// }}}

    // {{{ update
    private function update($param){
        if (!isset($param['key'])) throw new ConfigTaskException('Key name not set in "'.__FUNCTION__.'" operation. File('.basename($this->file).')');
        $key = $param['key'];
        if (isset($param['value'])) $value= $param['value'];
        elseif(isset($param['textValue'])) $value = $param['textValue'];
        else $value = "''";
        $value = trim($value);
        $section = isset($param['section'])?$param['section']:'base';

        if (!isset($this->k2l[$section])){ 
            io::info("\t[~PURPLE~U~~~] [".$section."] ".$key.' = '.$value);
            throw new ConfigTaskException('Section "'.$section.'" not found');
        }
        if (!isset($this->pattern[$section][$key])) return $this->create($param);

        io::info("\t[~PURPLE~U~~~] [".$section."] ".$key.' = '.$value);
        // equivalent consts
        if (substr($value,0,1) == '"') $v2 = substr($value,1,-1);
        else $v2 = $value;
        if ($this->pattern[$section][$key] == $v2) return;// io::done("\tchanges not needed");

        $this->addRollbackOperation('update', array('key' => $key, 'value' => $this->pattern[$section][$key], 'section' => $section) );


        $lines = $this->lines;
        $lines[ $this->k2l[$section][$key]] = $key.' = '.$value.PHP_EOL;

        $newini = $this->getPattern($lines);
        $diff = self::diffArray($newini, $this->pattern);
        if ($diff['del'] != 0 || $diff['add'] != 0 || $diff['modify'] != 1) 
            throw new ConfigTaskException('Error while updating constant. Given diff: '.implode('-', $diff));

        $this->lines = $this->processLines($lines, $newK2l);
        $this->pattern = $newini;
        $this->k2l = $newK2l;
    }// }}}

    // {{{ delete
    private function delete($param){
        if (!isset($param['key'])) throw new ConfigTaskException('Key name not set in "'.__FUNCTION__.'" operation. File('.basename($this->file).')');
        $key = $param['key'];
        if (!isset($param['section'])){
            foreach($this->pattern as $sect => $keys)
                if (isset($this->pattern[$sect][$key])){
                    $param['section'] = $sect;
                    $this->delete($param);
                }
            return;
        }

        $section = $param['section'];
        io::info("\t[~PURPLE~D~~~] [".$section."] ".$key);
        if (!isset($this->pattern[$section][$key])) 
            return io::out('Config key ['.$section.']['.$key.'] not found.', IO::MESSAGE_WARN);

        $this->addRollbackOperation('create', array('key' => $key, 'value' => $this->pattern[$section][$key], 'section' => $section) );

        $lines = $this->lines;
        unset($lines[ $this->k2l[$section][$key]]);
        $newini = $this->getPattern(array_values($lines));
        $diff = self::diffArray($newini, $this->pattern);
        if ($diff['del'] != 1 || $diff['add'] != 0 ||  $diff['modify'] != 0) 
            throw new ConfigTaskException('Error while delete constant. Given diff: '.implode('-', $diff));

        $this->lines = $this->processLines($lines, $newK2l);
        $this->pattern = $newini;
        $this->k2l = $newK2l;
    }// }}}

    // {{{ rename
    private function rename($param){
        if (!isset($param['to'])) throw new ConfigTaskException('New Key name( \"to\") not set in "'.__FUNCTION__.'" operation. File('.basename($this->file).')');
        $to = $param['to'];
        if (!isset($param['key'])) throw new ConfigTaskException('Key name not set in "'.__FUNCTION__.'" operation. File('.basename($this->file).')');
        $key = $param['key'];
        if (!isset($param['section'])){
            foreach($this->pattern as $sect => $keys)
                if (isset($this->pattern[$sect][$key])){
                    $param['section'] = $sect;
                    $this->rename($param);
                }
            return;
        }

        $section = $param['section'];
        io::info("\t[~PURPLE~R~~~] [".$section."] ".$key);
        if (!isset($this->pattern[$section][$key])) throw new ConfigTaskException('Config key ['.$section.']['.$key.'] not found', IO::MESSAGE_WARN);

        $this->addRollbackOperation('rename', array('key' => $to, 'to' => $key, 'section' => $section) );

        $lines = $this->lines;
        $li = $this->k2l[$section][$key] ;
        $a = explode('=', $lines[$li]);
        $a[0] = $to.' ';

        $lines[$li] = implode('=', $a);

        $newini = $this->getPattern($lines);
        $diff = self::diffArray($newini, $this->pattern);
        if ($diff['del'] != 1 || $diff['add'] != 1 ||  $diff['modify'] != 0) 
            throw new ConfigTaskException('Error while rename constant. Given diff: '.implode('-', $diff));

        $this->lines = $this->processLines($lines, $newK2l);
        $this->pattern = $newini;
        $this->k2l = $newK2l;
    }// }}}

    // {{{ addRollbackOperation
    /**
     *
     */
    private function addRollbackOperation($op, $param){
        if (!is_object($this->rollbackDir)) return;
        $file = $this->rollbackDir->getFile('operations.txt');
        if (!$file->exists()) $data = array();
        else $data = unserialize(file_get_contents($file));
        $data[] = array('operation'=> $op, 'param' => $param);
        file_put_contents($file, serialize($data));
    }// }}}

    // {{{ parseNode
    /**
     * Разбирает $this->node, возвращает массив операций
     *
     * @return array 
     */
    private function parseNode(){
        if (!is_null($this->node->attributes->getNamedItem('file')))
            $fileName = $this->node->attributes->getNamedItem('file')->nodeValue;
        else $fileName =basename(Config::getInstance()->getParsedFilename());
        $this->file =  Dir::get(Config::getInstance()->root_dir, true)->getDir('config')->getFile($fileName);

        // записываем полный путь файла для отката
        file_put_contents($this->rollbackDir->getFile('config.txt'), $this->file);

        $ops = array();
        $op = $this->node->childNodes;
        for ($i = 0; !is_null($item = $op->item($i++));)
            if (method_exists($this, $item->nodeName))
                $ops[] = array('operation' => $item->nodeName, 'param' => $this->nodeToArray($item));
        return $ops;
    }// }}}

    // {{{ nodeToArray
    /**
     * Конвертирует аттрибуты xml-узла в ассоциативный массив
     *
     * @param DOMNode $node
     * @return array
     */
    private function nodeToArray(DOMNode $node){
        for ($i =0; !is_null( $a = $node->attributes->item($i)); $i++)
            $res[$a->nodeName] = $a->nodeValue;
        if(!empty($node->nodeValue)) $res['textValue'] = $node->nodeValue;
        return $res;
    }// }}} 

    // {{{ diffArray
    static private function diffArray($new, $old, $returnCount = true){
        $add = $modify = $del = array();
        foreach($new as $sect => $keys ){
            if (!isset($old[$sect])){ $add[$sect] = $keys; continue;}
            foreach($keys as $key => $val){
                if (!isset($old[$sect][$key])) $add[$sect][$key] = $val;
                elseif ($old[$sect][$key] != $new[$sect][$key]) $modify[$sect][$key] = $val;
            }
        }

        foreach($old as $sect => $keys ){
            if (!isset($new[$sect])){ $del[$sect] = $keys; continue;}
            foreach($keys as $key => $val)
                if (!isset($new[$sect][$key])) $del[$sect][$key] = $val;
        }
        if ($returnCount) return array( 'add' => count($add), 'modify' => count($modify), 'del' => count($del));
    }// }}}
}
