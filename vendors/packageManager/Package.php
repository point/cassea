<?php


class PackageException extends CasseaException{}


class Package{
    const INSTALLED = 1;
    const DOWNLOADED = 2;
    const LOCALTED = 3;
    const FOUNDED = 4;

    private $file, $dir; // файл архива, и директория с распакованным содержанием
    private $xml;

    private $name;
    private $version;
    private $urls;

    /**
     * Статус пакета.
     *
     * Может быть одним из Package::INSTALLED, Package::DOWNLOADED, Package::LOCALTED, Package::FOUNDED.
     * @var int
     */
    private $status;

    private $deps;

    // info
    private $tags = array();
    private $summary;
    private $description;
    private $author_name;
    private $author_email;

    // {{{ __construct
    /**
     *
     *
     */
    function __construct($fileOrXml, $urls = null ){
        //io::out('Package __construct: '.(is_null($urls)?$fileOrXml:implode(', ',$urls)));
        $this->xml = new DomDocument();
        try{

            if ($fileOrXml instanceof iFile){
                $packageFile = $fileOrXml;
                if (!$packageFile->exists()) throw new PackageException('PackageFile('.$packageFile.') not exists.');

                $tmpDir = Dir::get(Config::getInstance()->root_dir, true)->getDir(Config::getInstance()->temp_dir);
                $xmlFile = Packer::unpackFile($packageFile, $tmpDir, 'package.xml');
                $this->xml->load($xmlFile);
                $this->file = $packageFile;
                $this->status = $this->determineStatus();
            }
            else{
                if (!is_array($urls) || count($urls)==0 ) throw new PackageException('Cant create package: list of URLs not set or empty.');
                $this->urls = $urls;
                $this->xml->appendChild($this->xml->importNode($fileOrXml, true));
                $this->status = Package::FOUNDED;
            }

            // TODO Валидировать xml-schemas
            $this->name = $this->xml->getElementsByTagName('name')->item(0)->nodeValue;
            $this->version = $this->xml->getElementsByTagName('version')->item(0)->nodeValue;
        }catch(DOMException $e){
            throw PackageException('Error while processing or parsing xml file with Message:'.$e->getMessage());
        }
    }// }}}

    // {{{ isPackage
    /**
     * Проверяет является ли указанный файл пакетом.
     *
     * В случае удачи возвращает объект Package, иначе false.
     * 
     * @param iFile
     * @return Package or bool false
     */ 
    static function isPackage(iFile $file){
        try{
            return $p = new Package($file);
        }
        catch(Exception $e){
            return false;
        }
    }// }}}

    // {{{ __get
    /**
     */
    function __get($property){
        if($property == 'deps'){
            if (is_null($this->deps)) $this->deps = Deps::parsePackageXml($this->xml);
            return $this->deps;
        } 
        if (in_array($property, array('file','name', 'version','status', 'xml')))
            return $this->$property;

        if (in_array($property, array('summary', 'description')))
            return $this->getProperty($property);

        if ($property == 'tags'){
            $tags = $this->getProperty($property);
            return is_null($tags)?'':preg_replace('#\s+#',' ',trim($tags));
        }
        
        if ($property == 'mantainer') 
            return $this->getProperty('mantainer/name').( !is_null($e= $this->getProperty('mantainer/email'))?" <$e>":''); 

        throw new PackageException('__get: unknown property '.$property);
    }// }}}

    // {{{ getProperty
    /**
     *
     */
    private function getProperty($q){
        $x = new DOMXPath($this->xml);
        $t= $x->query('//'.$q);
        if ( $t->length == 1 && trim( $s = $t->item(0)->nodeValue) != '' )
            return $s;
        return null;
    }// }}}

    // {{{ delivery
    /**
     *
     * @throws RepositoryException 
     */
    function delivery($targetFile){
        if ($this->file instanceof iFile && $this->file->getAbsPath() != $targetFile->getAbsPath())
            $this->file->copy($targetFile);
        else
            try { PackageManager::getRepositoryList()->fetchPackage($this->urls, $targetFile); }
            catch(RepositoryListException $e) {io::out('Error while fetching file');}

        if (($p = Package::isPackage($targetFile)) === false)
            throw new PackageException('Fetched file('.$targetFile.') isn\'t a package');
            
        return $p;
    }// }}}

    // {{{ determineStatus
    /**
     * Определяет статус пакета;
     *
     * Пакет должен быть создан на основе локального файла
     *
     * Определние произволится исходя из местоположения файла.
     * @return int
     */
    private function determineStatus(){
        $dDir = PackageManager::getDownloadDir();
        if ( substr($this->file, 0, strlen($dDir)) == $dDir) return Package::DOWNLOADED;
        $iDir = PackageManager::getInstalledDir();
        if ( substr($this->file, 0, strlen($iDir)) == $iDir) return Package::INSTALLED;
        return Package::LOCALTED;
    }// }}}

    // {{{ deploy
    /**
     * Размещение пакета
     *
     * @throws Exception if one of deploy task throws an exception
     */
    function deploy(){
        // распаковть в buildpad
        $bp = PackageManager::getBuildpadDir()->getDir($this->name.'_'.$this->version);
        PackageManager::getRollback()->push('delete', $bp);
        Packer::unpack($this->file, $bp);

        // Вырезать нужный кусок их Package.xml
        $rp = new DOMXPath($this->xml);
        $nodes = $rp->query('//deploy');
        if ($nodes->length == 0) return io::out('Nothing to deploy') | 0;
        $tasks = $nodes->item(0)->childNodes;

        // создать deployer
        $d = new Deployer($bp);
        $d->setTaskList($tasks);
        $d->executeTasks();

        // удалить директорию в билдпаде
        $bp->delete();
    }// }}}
}
