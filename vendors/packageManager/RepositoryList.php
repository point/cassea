<?php

class RepositoryListException extends CasseaException{}
/**
 *
 *
 */
class RepositoryList{
    private $path = null;
    private $list = null;
    private $context = null;
    // {{{ __construct
    /**
     * 
     * @param string absolute path to config file
     */
    function __construct(Dir $path){
        if (!$path->exists()) throw new RepositoryListException('Given path('.$path.') not exists.');
        $this->path = $path;
        $this->list = $path->getFile('list.txt');
        $this->createContext();
    }// }}}

    // {{{ createContext
    /**
     * Создает контекст для работы с удаленными файлами.
     *
     * Если есть, обрабатывает настройки в файле /config/RepositoryManager.ini
     */
    private function createContext(){
        $opts = array();        
        $conf = new File(Config::getInstance()->root_dir.'/config/RepositoryManager.ini',true);
        if ($conf->exists() && ($c = parse_ini_file($conf, 'protocols')) && isset($c['protocols']) && count(($c = $c['protocols'])))
            foreach($c as $k=>$v)
                $opts[substr($k,0,($p = strpos($k,'.')))][substr($k,$p+1)] = $v;

        $this->context = stream_context_create($opts);
    }//}}}

    // {{{ getList
    /**
     * Массив источников пакетов
     * @return array
     */
    function getList(){
        if (!is_readable($this->list))
            throw new RepositoryListException('Repositories list ('.$this->list.') not readable');
        $lines = file($this->list);
        $list = array();
        foreach($lines as $l) 
            if (substr($l = trim($l),0,1) != '#' && strlen($l) > 0 ) $list[] = trim($l);
        return $list;
    }// }}}

    // {{{ updateRepository
    /**
     * Обновляет указанный репозиторий
     *
     * @param string $url
     * @return int package count in repository 
     */
    function updateRepository($url){
        $parsed = $this->explodeURL($url);
        $l  = $this->getRepositoryFiles($url);
        $rmd5 = $this->getRemoteMd5($url);
        if ( $l['md5']->exists() &&  $rmd5  == file_get_contents($l['md5'])) return false;

        $t = $this->download(rtrim($url,'/').'/packages.lst',new TempFile());
        if ($rmd5 != md5_file($t))
            throw new  RepositoryListException('packages.lst('.$t->copy($t->getParent()->getFile($parsed['host'].'.'.$parsed['path'].'_packages.lst')).') not corespond given md5 hash ('.$rmd5.')');


        // TODO validate by xml-schema!
        try{
            $repo = new DOMDocument();
            $repo->load( $t );
            $xp = new DOMXPath($repo);
            $packageCount = $xp->query('//packages/package')->length;
        }
        catch(DOMException $e){
            throw new  RepositoryListException('Error while parsing given packages.lst('.$t->copy($t->getParent()->getFile($parsed['host'].'.'.$parsed['path'].'_packages.lst')).')');
        }
        $t->move($l['list']);

        if (!file_put_contents($l['md5'], $rmd5))
            throw new RepositoryListException('Error while updating local md5 hash('.$l['md5'].')');
        return $packageCount;
     }//}}}

    // {{{ getRemoteMd5
    /**
     * Получение хэша удаленного репозитория
     *
     * @param string $url 
     * @return string(32)
     */
    private function getRemoteMd5($url, $package=false){
        if (!$package)
            $md5file = $this->Download(rtrim($url,'/').'/packages.md5', new TempFile);
        else    
            $md5file = $this->Download( substr( $url,0, -4).'.md5', new TempFile);
        $md5 = trim(file_get_contents($md5file));
        if (preg_match('#^[A-Za-z0-9]{32}$#',$md5)) return $md5;
        if ($package) $url = substr($url, 0,  strrpos($url,'/packages/'));
        $parsed = $this->explodeURL($url);
        throw new RepositoryListException('Given incorrect md5 hash(stored in '.$md5file->copy($md5file->getParent()->getFile($parsed['host'].'.'.$parsed['path'].'_'.($package?'package':'packages').'.md5')).')');
    }// }}}

    // {{{ download
    /**
     * TODO скачивание стрипами без ограничения размера
     * PHP 5.3 copy
     *
     */
    private function download($url, $target = false){
        $data = file_get_contents($url, 0, $this->context);
        if ($data === false)
            throw new RepositoryListException('Download '.$url.' failed',100);
        if (!($target instanceof iFile)) return $data;
        if (!file_put_contents($target, $data))
            throw new RepositoryListException('Download '.$url.' failed: cant write downloaded content to '.$target,100);
        return $target;
       }// }}}

    // {{{ getRepositoryFiles
    /**
     * Создает объекты локальных файлов которые содержат
     * информацию о хранилище $url.
     *
     * 
     * @param string $url
     * @return array('list'=>File, 'md5'=>file)
     */
    private function getRepositoryFiles($url){
        $p = $this->explodeURL($url);
        $name= $p['host'].'.'.$p['path'].(($p['scheme']!='http')?('_'.$p['scheme']):'');
        return array( 
            'list' => $this->path->getFile($name.'.lst'),
            'md5' => $this->path->getFile($name.'.md5')
        );
    }// }}}

    // {{{ explodeURL 
    /**
     * валидирует адресс хранилища и представляет ввиде компонент.
     *
     * Парсит $url, проверяет его корректность, дополняет пропущенные значения
     * 
     * @throws RepositoryListException если нет указан хост
     * @param string $url
     * @retrun array('scheme'=> string, 'host' => string, 'path' => string )
     */
    private function explodeURL($url){
        $p = parse_url($url);
        if (!isset($p['host'])) throw new RepositoryListException('Incorrect URL('.$url.'). Host not finded.');
        if (strpos($p['path'], '.') !== false ) throw new RepositoryListException('Incorrect URL('.$url.'): path can\'t contain "."');
        if (strpos($p['path'], '-') !== false ) throw new RepositoryListException('Incorrect URL('.$url.'): path can\'t contain "-"');
        return array(
            'scheme' => isset($p['scheme'])?$p['scheme']:'http',
            'host' => $p['host'],
            'path' => str_replace('/','-',trim($p['path'],'/'))
        );
    }// }}} 

    // {{{ buildPackageUrl
    /**
     * Строит URL по которому должен быть доступена версия пакета в указанном хранилище.
     */
    private function buildPackageUrl($repositoryFile, $package, $version){
        $repo = basename($repositoryFile,'.lst');
        $path = '/'.str_replace('-','/',substr($repo, strrpos($repo,'.')+1));
        $host = substr($repo, 0, strpos($repo, '.'));
        return 'http://'.$host.$path.'/packages/'.$package.'_'.$version.'.tbz';
    }// }}} 

    // {{{ updateRepositories
    /**
     * Обновляет все репозитории
     */
    function updateRepositories(){
        foreach($this->getList() as $url)
            $this->updateRepository($url);
    }// }}}

    // {{{ SearchPackage
    /***
     * Поиска пакета максимально подходящего под требования.
     *
     * @param array $nvr
     * return $package;
     */
    function searchPackage($nvr){
        $name = $nvr['name'];
        $version = $nvr['version'];
        $rel = $nvr['rel'];

        // normalize NRV
        if (is_null($version)){
            $version = '0.0';
            $rel = '>=';
        }
        elseif(is_null($rel)) $rel = '=';

        // Search matched name packages
        $l = $this->path->ls('*.lst');
        $res = array();
        io::out('Searching '.$name.' '.$rel.' '.$version.': ', false);
        foreach($l as $list){
            $dom = new DomDocument();
            $dom->load($list);
            $xp = new DOMXPath($dom);
            $r = $xp->query("//package[name='".$name."']");
            $vStr = array();
            for($i = 0, $c = $r->length; $i<$c; $i++){
                $e = $r->item($i);
                $vStr[] = $ver = $e->getElementsByTagName('version')->item(0)->nodeValue;
                $res[$ver][] = $this->buildPackageUrl($list, $name, $ver);
                $xml[$ver] = $e;

            }
            if (count($vStr)) io::out(basename($list, '.lst').' ['.implode(', ',$vStr).'] ', false);
        }

        io::out();

        if (!count($res)) throw new RepositoryListException('Package '.$name.' not found.');
        krsort($res);

        foreach ($res as $v => $urls)
            if (version_compare($v, $version, $rel))
                return new Package($xml[$v], $urls);

        throw new RepositoryListException('Package '.$name.' '.$rel.' '.$version.' not found');
    }// }}}

    // {{{ getAvaibleVersions
    /** 
     * Возвращфат все доступные в репозиториях версии пакета $name
     *
     *
     */
    function getAvaibleVersions($name){

        $res = array();
        // Search matched name packages
        $l = $this->path->ls('*.lst');
        $res = array();
        //io::out('Searching '.$name.': ', false);
        foreach($l as $list){
            $dom = new DomDocument();
            $dom->load($list);
            $xp = new DOMXPath($dom);
            $r = $xp->query("//package[name='".$name."']");
            $vStr = array();
            for($i = 0, $c = $r->length; $i<$c; $i++){
                $e = $r->item($i);
                $vStr[] = $ver = $e->getElementsByTagName('version')->item(0)->nodeValue;
                $res[$ver] = new Package($e, array($this->buildPackageUrl($list, $name, $ver)));
            }
            //if (count($vStr)) io::out(basename($list, '.lst').' ['.implode(', ',$vStr).'] ', false);
        }
        ksort($res);
        return array_values($res);
    }// }}}

     // {{{ fetchPackage
    /**
     * Загружает пакет указанный в cgbcrt $url в $targetFile.
     * 
     * Проверяет хеш пакета. Останавливается после первого скачанного файла.
     *
     * @trows RepositoryListException 
     * @param array $urls list of URLS where package couldbe found
     * @param File $targetFile local file where fetched package has ben stored/
     * @return File $targetFile if fetching successfully.
     *
     *
     */
    function fetchPackage($urls, $targetFile){
        foreach($urls as $url)
            try{
                $targetFile = $this->download($url, $targetFile);
                $remoteMd5 = $this->getRemoteMd5($url, true);
                if ($remoteMd5 == md5_file($targetFile)){
                    io::out(' '.$url.' ( '.sizeToString(filesize($targetFile)).' )', false);
                    return $targetFile;
                }
                else io::out('Downloaded file '.$targetFile.' not correspond to its md5 sum('.$remoteMd5.')');
            }
        catch(RepositoryListException $e){
            if ($e->getCode() != 100 ) throw $e;
        }
        throw new RepositoryListException('Package '.basename($url[0].' not found. Given urls: '.implode(PHP_EOL.', ', $urls)));
    }// }}}

    // {{{ fetch
    /**
     * Доставка пакетa на локальную машину
     *
     */
    function fetch($package, $version, $rel, $targetDir){
        // normalize NRV
        if (is_null($version)){
            $version = '0.0';
            $rel = '>=';
        }
        elseif(is_null($rel)) $rel = '=';

        // Search matched name packages
        $l = $this->path->ls('*.lst');
        $res = array();
        foreach($l as $list){
            io::out('Processing '.basename($list, '.lst'), false).': ' ;
            $dom = new DomDocument();
            $dom->load($list);
            $xp = new DOMXPath($dom);
            $r = $xp->query("//package[name='".$package."']");
            for($i = 0, $c = $r->length; $i<$c; $i++){
                $e = $r->item($i);
                $ver = $e->getElementsByTagName('version')->item(0)->nodeValue;
                io :: out(' '.$ver.' ',false);
                $res[$ver][] = $this->buildPackageUrl($list, $package, $ver);

            }
            io::out();
        }

        if (!count($res)) throw new RepositoryListException('Package '.$package.' not found.');
        krsort($res);
        foreach ($res as $v => $urls) {
            if (version_compare($v, $version, $rel)){
                //IO::OUt($v. ' '.$rel.' '.$version);
                $f = $targetDir->getFile($package.'_'.$v.'.tbz');
                foreach($urls as $url)
                    try{
                        $f = $this->download($url, $f);
                        $remoteMd5 = $this->getRemoteMd5($url, true);
                        if ($remoteMd5 == md5_file($f)) return $f;
                        else io::out('Downloaded file '.$f.' not correspond to its md5 sum('.$remoteMd5.')');
                    }
                    catch(RepositoryListException $e){
                        if ($e->getCode() != 100 ) throw $e;
                    }
            }

        }
        throw new RepositoryListException('Package '.$package.$rel.$version.' not found');
    }// }}}

     // {{{ addRepository
    /**
     * Добавляет источник
     *
     * @param string источник
     * @return bool true если добавление прошло успешно, false если источник уже существует.
     */
    function addRepository($url){
        $this->explodeURL($url);
        if( in_array($url, $this->getList()) ) return false;

        $str = str_replace('####'.$url, $url, file_get_contents($this->list), $count);
        $str =$count?$str:PHP_EOL.'### Added automaticaly'.PHP_EOL. '### Date: '.date('M d Y H:i:s' ).PHP_EOL.$url.PHP_EOL;

        if(!file_put_contents($this->list, $str, $count?0:FILE_APPEND ))
            throw new RepositoryException('Error while adding source "'.$url.'" to source list "'.$this->list.'"');
        return true;
    }// }}} 

    // {{{ deleteRepository 
    /**
     * Удаляет (Комментирует) источник.
     * 
     * @param string источник
     * @return bool true если удаление прошло успешно, false если источник не существует в source liste.
     */
    function deleteRepository($url){
        foreach ($this->getRepositoryFiles($url) as $f) $f->delete();        
        if (!in_array($url, $this->getList()) ) return false;
        if (!file_put_contents($this->list, str_replace($url, '####'.$url, file_get_contents($this->list))))
            throw new RepositoryException('Error while deleting  source "'.$url.'" to source list "'.$this->list.'"');
        return true;
    }// }}} 

    // {{{ flushRepositories
    /**
     * Удаляет локальные копии packages.md5 и packages.lst
     * 
     */
    function flushRepositories(){
        foreach ($this->path->ls('*.md5') as $p ) $p->delete();
        foreach ($this->path->ls('*.lst') as $p ) $p->delete();
    }// }}}
}
