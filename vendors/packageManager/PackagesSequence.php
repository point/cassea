<?php 
/**
 * @see 6.15.10.2009
 */

class PackagesSequenceException extends CasseaException{}


/**
 * Последовстельность установки пакетов.
 *
 *
 * TODO блокировка файла последовательности.
 */
class PackagesSequence{
    private $file;
    // {{{ __construct
    /**
     */
    function __construct($path = null){
        $this->file = is_null($path)?PM::getDataDir()->getFile('packagesSequence.txt'):$path;
        if (!is_writable($this->file)) throw new PackagesSequenceException('Sequence file ('.$this->file.') not writable.');
    }// }}}

    // {{{ get
    function get(){
        foreach(file($this->file) as $l)
            if (preg_match('!^([^#]\S+)\s+(\d+.\d+)\s+(\S+)(\s+(.*)?)?$!',trim($l),$m)) 
               $sequence[] = array(
                   'name' => $m[1],
                   'version'=> $m[2],
                   'repository' => $m[3],
                   'comment' => isset($m[5])?trim($m[5]):''
               );
        return isset($sequence)?$sequence:array();
    }// }}}

    //{{{ getAfter
    /**
     *
     */
    function getAfter($package, $version){
        $res = $this->get();;
        for ($i =0 , $c = count($res); $i < $c; $i++)            
            if ( $res[$i]['name'] == $package && $res[$i]['version'] == $version ) return array_values(array_slice($res, $i+1));
        throw new PackagesSequenceException('Package '.$package.'('.$version.') not found in Sequence list.');
    }//}}}

    // {{{ addPackage
    /**
     *
     *
     */
    function addPackage($package, $version, $repository ='unknown', $comment = null){
        foreach($this->get() as $p )
            if ( $p['name'] == $package && $p['version'] == $version ) throw new PackagesSequenceException('Package '.$package.'('.$version.') exists in list.');

        if (is_null($comment)) $comment = 'Install date '.date('M d Y H:i:s');
        if (!file_put_contents($this->file, $package."\t".$version."\t".$repository."\t".$comment.PHP_EOL, FILE_APPEND))
            throw new PackagesSequenceException('Error while writing Sequence file ('.$this->file.').');
    }// }}}
    
    // {{{ removePackage
    /**
     * Удаление из истории установки указанного пакета;
     */
    function removePackage($package, $version){
        $lines = file($this->file);
        $res = array();
        foreach($lines as $l)
            if (!preg_match('!^('.trim($package).')\s+('.trim($version).')\s+(\S+)(\s+(.*)?)?$!',trim($l),$m)) $res[] = $l;
        if (count($lines) == count($res) + 1)
            file_put_contents($this->file, implode('', $res));
        else throw new PackagesSequenceException('Package '.$package.'('.$version.') not found in Sequence list.');
    }// }}}
    
}
