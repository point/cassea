<?php


// {{{ DepsItem 
/**
 * Фильтр зависимостей для пакета.
 *
 * Изначально объект конструируется со списком доступных версий.
 * По мере присоеденения требований(merge) из сиписка исключаются
 * не удовлетворяющие версии.
 *
 */
class DepsItem{
    /**
     * Имя пакета
     * @var string
     */
    private $name;

    /**
     * Список состояний массива filtered, для откатов 
     * в случае неуспешного слияния требований(merge)
     * @var array
     */ 
    private $backup = array();

    /**
     * Массив пакетов удовлетворяющих зависимостям после всех слиний(merge).
     * @var array of Packages
     */
    private $filtered = array();

    /**
     * Глубина зависимости
     *
     * Первым ставится пакет с большей глубиной.
     *
     * Например если пакет А зависит от пакета Б, а пакет Б зависит от пакета В,
     * то глубина зависимостей будет 0, 1, 2 для пактов А, Б, В соответсвенно.
     * Отсюда следует что порядок установки будет В, Б, А.
     * @var int
     */
    private $deep = 0;
    
    // {{{ __construct
    /**
     * Конструктор формирует массив доступных версий для пакету $name
     * В конструктор передан объект Package, который будет еслинственным
     * елементом массива filtered
     * 
     * @param string $name имя пакета
     */
    function __construct($packageName){
        if ($packageName instanceof Package){
            $this->name = $packageName->name;
            $this->filtered = array($packageName);
        }
        else{
            $this->name= $packageName;
            $this->filtered = PackageManager::getRepositoryList()->getAvaibleVersions($packageName);
        }

        //print_pre(count($this->filtered));
        //foreach($this->filtered as $p) io::out($p->name.' '.$p->version);
        $iPackage = PackageManager::getInstalledPackage($packageName);
        if ($iPackage instanceof Package){
            // Оставляем версии больше установленной
            $this->merge($iPackage->version, 'gt');

            // добавляем в начало массива установленную версию.
            // Она будет самой младшей версией из доступных.
            array_unshift($this->filtered, $iPackage);
        }
        //foreach($this->filtered as $p) io::out($p->name.' '.$p->version);
    }// }}}

    // {{{ getPackageName
    /**
     * Имя пакета за который отвечает объект.
     * @return string
     */
    function getPackageName(){
        return $this->name;
    }// }}}

    // {{{ getFiltered
    /**
     * Список пакетов удовлетворяющий зависимостям
     *
     * @param bool $best if true return best package.
     * @return array of Packages
     */
    function getFiltered( $best = false){
        if ($best) return $this->filtered[count($this->filtered)-1];
        return $this->filtered;

    } // }}} 

    //  {{{ getFilteredStr
    /**
     * Строка из отфильтрованных версий
     * @return string
     */
    function getFilteredStr(){
        $res = array();
        foreach($this->filtered as $f) $res[] = $f->version;
        return implode(', ',$res);
    }// }}}

    // {{{ popNext
    /**
     * выталкивает следующую версию пакета
     *
     * @return package
     */
    function popNext(){
        return array_pop($this->filtered);
    }// }}}

    // {{{ pushBack
    /**
     * Помещает пакет обратно в очередь после неудачной его обработки.
     *
     * @return int количество элементов в массиве filtered
     */
    function pushBack($package){
        return array_push($this->filtered, $package);
    }// }}}

    // {{{ merge
    /**
     * Присоедениеть новую зависимость.
     *
     * Добавляет новое требование к верссии пакета.
     * Обновляет массив допустимых версий $filtered.
     *
     * Если добавить невозможно возвращает false.
     *
     * @param string $version требуемая версия пакета
     * @param string $rel отношение версии
     * @return bool false if conflict found, true otherwise
     */ 
    function merge($version , $rel){
        $this->backup[] =  $this->filtered;
        $this->filtered = array_values(array_filter($this->filtered, 
            create_function('$f', 'return version_compare($f->version,"'. $version.'", "'.$rel.'");')  ));
        return count( $this->filtered ) != 0;
    }// }}}

    // {{{ unmerge
    /**
     * Откат на предыдущий набор пакетов.
     *
     * @return bool false
     */
    function unmerge(){
        $this->filtered = array_pop($this->backup);
        return false;
    }// }}}

    // {{{ updateDeep
    /**
     * Обновляет глубину зависимости.
     *
     * Устанавливается большая из глубин $newDeep и $this->deep.
     *
     * @param int $newDeep
     */
    function updateDeep($newDeep){
        if ($this->deep < $newDeep) $this->deep = $newDeep;
    }// }}}

    // {{{ getDeep
    /** 
     * Получение глубины зависимости
     *
     * @return int глубина зависимости
     */
    function getDeep(){
        return $this->deep;
    }// }}}

}// }}}


// {{{ Deps
/**
 * Класс вычисляет список пакетов необходимыя для удовлетворения
 * зависимостей пакета переданного через функцию push.
 */
class Deps{
    /**
     * Очередь(массив) зависимостей DepsItem
     * @var array of DepsItem
     */
    private $queue = array();

    // {{{ parsePackageXml
    /**
     * Выделяет из xml(DOMDocument построенный из package.xml) зависимости
     * и возвращает массим с ключами php, phpIniValues, phpExtensions, packages.
     *
     * Этот массив используется для расчета зависимостей и проверки 
     * параметров окружения.
     *
     * @param DOMDocument $xml
     * @return array
     */
    static function parsePackageXml(DOMDocument $xml){
        $xp = new DOMXPath($xml);
        $res = array(
            'php' => null,
            'phpIniValues' => array(),
            'phpExtensions' => array(),
            'packages' => array()
        );
        // php
        $r = $xp->query('//depends/php');
        if ($r->length == 1 && !is_null($attr = $r->item(0)->attributes))
            $res['php'] = array( 
                'version' => $attr->getNamedItem('version')->nodeValue,
                'rel' => is_null($rel = $attr->getNamedItem('rel'))?'=': self::normalizeRel($rel->nodeValue)
            );

        // php extensions
        $r = $xp->query('//depends/php_extension');
        for ($i = 0; $i < $r->length; $i ++)
            if (!in_array(($ext = trim($r->item($i)->nodeValue)), $res['phpExtensions']))
                $res['phpExtensions'][] = $ext;


        // php ini_value
        $r = $xp->query('//depends/php_ini_value');
        for ($i = 0; $i < $r->length; $i ++){
            $attr = $r->item($i)->attributes;
            $res['phpIniValues'][] = array( 
                'name' => $attr->getNamedItem('name')->nodeValue,
                'rel' => is_null($rel = $attr->getNamedItem('rel')->nodeValue)?'=':self::normalizeRel($rel),
                'value' => $attr->getNamedItem('value')->nodeValue
            );
        }

        $r = $xp->query('//depends/package');
        for ($i = 0; $i < $r->length; $i ++){
            $attr = $r->item($i)->attributes;
            $name = is_null($attr->getNamedItem('name'))?null:$attr->getNamedItem('name')->nodeValue;
            $version = is_null($attr->getNamedItem('version'))?null:$attr->getNamedItem('version')->nodeValue;
            $rel = is_null($attr->getNamedItem('rel'))?null:self::normalizeRel($attr->getNamedItem('rel')->nodeValue);

            if (is_null($version)){
                $version = '0.0';
                $rel = '>=';
            }
            elseif(is_null($rel)) $rel = '=';

            $res['packages'][] = $p = array('name' => $name, 'version' =>$version, 'rel' => $rel); 
        }
        return $res;
    }// }}}

    // {{{ normalizeRel
    static function normalizeRel($rel){
        $r = array(
            'gt' => '>',
            'ge' => '>=',
            'lt' => '<',
            'le' => '<=',
            'eq' => '=',
            '==' => '=',
            'ne' => '!=',
            '!'  => '!=',
            '<>' => '!='
        ); 
        return in_array($ret = str_replace(array_keys($r), array_values($r), $rel), array_values($r))?$ret:null;
    }// }}}

    // {{{ checkEnviroment
    /** 
     * Проверка параметров окружения
     *
     * @param array 
     * @return bool
     */
    private function checkEnviroment($d){
        $ret = true;

        if (is_array($d['php']) && !version_compare(phpversion(),$d['php']['version'], $d['php']['rel'])) $ret &= false;

        $ext = get_loaded_extensions();
        foreach( $d['phpExtensions'] as $name){
            $ret &=  $a = in_array($name, $ext);
            if (!$a) io::info($name,  IO::MESSAGE_FAIL);
        }

        return $ret;

        // TODO
        // обработка 
        //   - размеров фалов(k,b,m)
        //   - строковые значения
        foreach( $d['phpIniValues'] as $v){
            $cv = ini_get($v['name']);
            if ($cv === true) $cv = 'true';
            else if ($cv ===false) $cv= 'false';
            else if (is_null($cv)) $cv= 'NULL';
            $ret &= $a = version_compare($cv, $v['value'], $v['rel'] );
            if(!$a) io::info($v['name'].' '.$v['rel'].' '.$v['value'].'  current value:'.$cv, IO::MESSAGE_FAIL);
        }
        return $ret ;
    }// }}}

    // {{{ calculate 
    /**
     * 
     */
    static function calculate( $package ){
        $deps = new Deps();

        if ( $package instanceof Package)
            $di = new DepsItem($package);
        else{
            $nvr = PackageManager::parseNRV($package);
            $di = new DepsItem($nvr['name']);
            $di->merge($nvr['version'], $nvr['rel']);
            if ( count($di->getFiltered()) == 0) return false;
        }

        //if ( count($di->getFiltered()) == 1 && 
        // Лучший из доступных пакетов установлен?
        if ( PackageManager::getInstalledPackage($di->getPackageName(), $di->getFiltered(true)->version))
            return  $di->getFiltered(true);

        $deps->queue = array(&$di);

        while ( !is_null($fpi = $di->popNext()) && !$deps->push($fpi))
            $deps->queue = array(&$di); // restore queue

        if (is_null($fpi)) return false;

        $di->pushBack($fpi);

        return $deps->getPackagesToDeploy();
    }// }}}

    // {{{ push
    /**
     * Добавление пакета в очередь
     *
     * @param Package $f package to add
     * @pad string optiontal parametr to finest output
     * @return true if pckage added and all depnedencies is statisfied.
     */
    private function push(Package $f, $deep = 1, $pad = ''){

        io::info($pad.'    '.$f->name.' '.$f->version.'{');
        $oldPad= $pad.'    ';
        $pad.= "\t";

        $packageDeps = $f->deps;
        // check env dependencie
        static $cached = array();        
        if (!isset($caches[$f->name][$f->version]))
            if (!$this->checkEnviroment($packageDeps)) return false;
        $caches[$f->name][$f->version] = true;

        foreach($packageDeps['packages'] as $nvr){
            //io::out($pad."deps: ".$nvr['name'].' '.$nvr['rel'].' '.$nvr['version']);
            if (($d = $this->searchInQueue($nvr['name'])) === false){
                $d = new DepsItem($nvr['name']);
                $this->queue[] = $d;
            }

            io::info($pad.$d->getPackageName().': ( '.$d->getFilteredStr(). ' )', false );
            io::info(' '.$nvr['rel'] .' '.$nvr['version'].'   : ', false);
            if(!$d->merge($nvr['version'], $nvr['rel']))
                io::info('~RED~Unable merge version. trying other one.~~~');
            else io::info($d->getFilteredStr());
            $d->updateDeep($deep);

            while ( !is_null($fpi = $d->popNext()) && !$this->push($fpi, $deep+1, $pad));
            
            // доступные версии пакета закончились раньше чем push вернул true;
            // те неудовлетворенная зависимость.
            if (is_null($fpi)) return $d->unmerge(); // false
            // найден пакет удовлетворяющий зависимостям.
            // запихиваем его обратно зависимость
            $d->pushBack($fpi);
        }
        io::info($oldPad.'}');
        return true;
    }// }}}

    // {{{ searchInQueue
    /**
     * Проверяет существование зависимости для пакета @name в очереди.
     *
     * Возвращает зависимость для пакета,
     * если ее не существует вернет false.
     *
     * @param string $name имя пакета
     * @return DepsItem 
     */
    private function searchInQueue($name){
        foreach($this->queue as $di) 
            if ($di->getPackageName()== $name) return $di;
        return false;
    }//}}}

    // {{{ getPackagesToDeploy
    /**
     * Возвращает пакеты которые удовлетворяют все предъявленным зависимостям.
     *
     * Метод вызывается после проверки всех зависимостей(после того как верхний push вернул true)
     * для их скачивания(если надо) и размещения.
     *
     * Массив пакетов возвращается в порядке скачивания и установки.
     *
     * @return array of Packages
     */
    function getPackagesToDeploy(){
        $out = array();
        $res = array();
        foreach($this->queue as $d){
            $res[$d->getDeep()][] = $d;
        }
        krsort($res);

        foreach($res as $r)
            foreach($r as $rr) $out[] = $rr->getFiltered(true);
        return $out;
    }// }}}


    //{{{ isNoNeeded
    /**
     * Проверять возможность удаления указанного пакета.
     *
     * Если существует пакет который зависит от данного то удаление невозможно.
     *
     * Массив пакетов для удаления. Если удаление невозможно вернет false.
     *
     * @param $nvr
     * @param array $unstatisfied
     * @return array of packages | bool false 
     */
    static function isNoNeeded($nvr, &$unstatisfied){
        $name = $nvr['name'];
        $pseq = PackageManager::getPackageSequence();
        $nvr['rel'] = Deps::normalizeRel($nvr['rel']);
        if ($nvr['rel'] == '=') $nvr['rel'] = '>=';
       
        $stayedList = array(); // версии которые останцтся после уддаления
        $removedList = array();// версии которые необходимо удалить
        $addToRemovedList = false;
        $checkList = array(); // пакеты которые необходимо проверить на совместимость
        for ($i =0, $c = count($pseq); $i < $c; $i++)
            if ($pseq[$i]['name'] != $name){
                if( $addToRemovedList) $checkList[] = $pseq[$i];
            }
            else{
                if (version_compare($pseq[$i]['version'], $nvr['version'],$nvr['rel'])) $addToRemovedList = true;
                if ($addToRemovedList) $removedList[] = PackageManager::getInstalledPackage($pseq[$i]['name'], $pseq[$i]['version']);
                else $stayedList[] = $pseq[$i];
            }
        /*
        io::out('Stayed list : '.$name, false);
        foreach($stayedList as $a) io::out($a['name'].'='.$a['version']. ' ', false);
        io::out();

        io::out('removes list : ', false);
        foreach($removedList as $a) io::out($a->version. ' ', false);
        io::out();

        io::out('CheckList : ', false);
        foreach($checkList as $a) io::out($a['name'].' '.$a['version'].', ', false);
        io::out();
         */

        foreach($checkList as $pnvr){
            $packageDeps = PackageManager::getInstalledPackage($pnvr['name'], $pnvr['version'])->deps['packages'];
            foreach($packageDeps as $p)
                if ($p['name'] == $name){
                    $statisfy = false;
                    $luns = array(); // localUnstatisfied
                    //io::out($nvr['name'].' '.$nvr['version']);
                    //print_r($p);
                    // Все версии пакета будут удалены, а пакет фигурирует в зависимостях
                    if (count($stayedList) == 0)
                            $luns[$p['name'].' '.$p['rel'].' '.$p['version']] = 1;
                    else
                    foreach($stayedList as $stayedPackage)
                        if (!version_compare($stayedPackage['version'], $p['version'], $p['rel']))
                            $luns[$p['name'].' '.$p['rel'].' '.$p['version']] = 1;
                        else {
                            $statisfy = true;
                            break;
                        }
                    // если после удаления не останется ниодного пакета удовлетворяющего зависимости,
                    // то добавляем в массив нудовлетворенных зависимостей $luns
                    if (!$statisfy) $unstatisfied[$pnvr['name'].'('.$pnvr['version'].')'] = $luns;
                }
        }

        return count($unstatisfied)?false:array_reverse($removedList);
    }// }}}
}


