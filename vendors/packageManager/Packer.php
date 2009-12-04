<?php


class PackerException extends CasseaException{}


/**
 * Un/Packing function
 *
 */
class Packer{
    /**
     * TODO вынести в конфиг
     */
    static private $config = array(
        'unpack_template' => 'tar -xaf {FILE} -C {TARGET} 2>&1',
        'pack_template' => 'tar -cjf {TARGET} -C {FILE} . 2>&1',
        'unpack_file_template' => 'tar -C {DIR} -xaf {ARCHIVE} {FILE} 2>&1',
        'logDir' => 'logs'
        
        );

    // {{{ unpack
    /**
     * Распаковывает $archive в указаную папку 
     *
     */
    static function unpack(File $archive,Dir $target){
        if (!$archive->canRead())
            throw new PackerException('File archive '.$archive.' not readable.');

        if (!$target->canWrite() && !$target->getParent()->canWrite())
            throw new PackerException('Folder  '.$target.' not writable.');
        $target->mkdir();
        $cmd = str_replace(array('{FILE}','{TARGET}'), array($archive, $target), self::$config['unpack_template']);

        exec($cmd, $out, $res);
        if ($res){
            $out[] = PHP_EOL.'Exit code: '.$res.PHP_EOL;
            $f = Dir::get(Config::getInstance()->root_dir, true)->getDir(self::$config['logDir'])->getFile('unpack_'.basename($archive).'.log');
            file_put_contents($f, implode(PHP_EOL, $out));
            throw new PackerException('Error while unpacking. Return code: '.$res.'. Log file stored at '.$f);
        }
    }// }}}

    // {{{ unpackFile
    /**
     * Распаковывает $archive в указаную папку 
     *
     */
    static function unpackFile(File $archive, Dir $target, $filename){
        if (!$archive->canRead())
            throw new PackerException('File archive '.$archive.' not readable.');

        if (!$target->canWrite())
            throw new PackerException('Target folder '.$target.' not writable.');

        $cmd = str_replace(array('{ARCHIVE}','{DIR}','{FILE}'), array($archive, $target, $filename), self::$config['unpack_file_template']);

        exec($cmd, $out, $res);
        if ($res){
            $out[] = PHP_EOL.'Exit code: '.$res.PHP_EOL.'Command: '.$cmd.PHP_EOL;
            $f = Dir::get(Config::getInstance()->root_dir, true)->getDir(self::$config['logDir'])->getFile('unpackFile_'.basename($archive).'.log');
            file_put_contents($f, implode(PHP_EOL, $out));
            throw new PackerException('Error while unpacking. Return code: '.$res.'. Log file stored at '.$f);
        }
        return $target->getFile($filename);
    }// }}}

    // {{{  pack 
    /**
     *
     */
    static function pack(Dir $source, File $target){
        if (!$source->exists()) throw new PackerException('Source folder '.$source.' not readable.');
        if (!$target->getParent()->canWrite()) throw new PackerException('Target file '.$target.' not writable.');

        $cmd = str_replace(array('{FILE}','{TARGET}'), array($source, $target), self::$config['pack_template']);
        exec($cmd, $out, $res);
        if ($res){
            $out[] = PHP_EOL.'Exit code: '.$res.PHP_EOL;
            $f = Dir::get(Config::getInstance()->root_dir, true)->getDir(self::$config['logDir'])->getFile('pack_'.basename($source).'.log');
            file_put_contents($f, implode(PHP_EOL, $out));
            throw new PackerException('Error while unpacking. Return code: '.$res.'. Log file stored at '.$f);
        }
    } // }}}
}
