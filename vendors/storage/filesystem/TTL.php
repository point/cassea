<?php



class TTL{
    static $ttlDir = null;
    static $namesDir = null;
    static $list = array();

    static function init(){
        $d = FilesystemStorage::getDir('')->getParent();
        self::$ttlDir = $d->getDir('ttl');
        self::$namesDir = $d->getDir('names');
        unset($d);
    }

    static function queued($name, $ttl){
        //print_pre('<b>Q</b> ' .$name.' '.$ttl);
        self::$list[$ttl][]=$name;
    }

    static function getTime($ttl = 0){
        static $curtime = null;
        if (is_null($curtime)) $curtime = time();
        // return ($curtime + $ttl) / 60;
        return $curtime + $ttl;
    }

    static function updateGroup( $tries = false){
        //print_pre(__FUNCTION__);
        $removeList = array();
        try{
            foreach(self::$list as $ttl => $names){
                $dieTime = self::getTime($ttl);
                foreach($names as $name){
                    $nameFile = self::$namesDir->getFile($name);
                    //if ($nameFile->exists() && ($aTTL = $nameFile->content) > self::getTime() )
                    if ($nameFile->exists() ){
                        $aTTL = $nameFile->content;
                        $removeList[$aTTL][] = $name;
                    }
                    $nameFile->content = $dieTime;
                }
                self::updateTTLFile($names, self::$ttlDir->getFile($dieTime));
            }
            foreach($removeList as $aTTL => $names )
                self::cleanTTLFile($names,  self::$ttlDir->getFile($aTTL));
        }
        catch(FileSystemException $e ){
            if ($tries) throw $e;
            try{
                self::$ttlDir->mkdir();
                self::$namesDir->mkdir();
                self::updateGroup(  true);
            }
            catch(FileSystemException $e2){ echo "e2";throw $e2;}
        }
    }

    static private function updateTTLFile($names, $file){
        $ttlNames = self::getNames($file);
        foreach( $names  as $name ) $ttlNames[] = $name;
        self::putNames($file, array_unique($ttlNames));
    }

    static private function cleanTTLFile($removeNames,  $file){
        $names = self::getNames($file);
        $least = array_diff($names, $removeNames);
        self::putNames($file, $least);
    }

    static private function getNames($file){
        if ($file->exists())  return explode(PHP_EOL, $file->content );
        return array();
    }

    static private function putNames($file, $names){
        if (count($names)) $file->content = implode(PHP_EOL, $names);
        else $file->delete();
    }

    static function cleanup( $limit = 100){
        $cleanTime = self::getTime();
        $lock =  FilesystemStorage::getDir('')->getFile('cleanupLock.txt');
        try{
            if ($lock->exists() && ($cleanTime - $lock->content) < 3600 ) return;
            $lock->content =  $cleanTime;
        }catch(FileSystemException $e){
            if (!$lock->exists()) $lock->getParent()->mkdir(); 
            $lock->content =  $cleanTime;
        }


        if ( is_resource($dh = @opendir(self::$ttlDir)))
            while ( ($fname = readdir($dh)) !==  false && $limit--)
                if ($fname < $cleanTime && $fname != '..' && $fname != '.'){
                    try{ 
                        foreach( self::getNames($ttlFile = self::$ttlDir->getFile( $fname )) as $name){
                            FilesystemStorage::destroy($name);
                            self::$namesDir->getFile($name)->delete();
                        }
                        $ttlFile->delete();
                    }catch(FilesystemStorage $e){ file_put_contents('1.txt','!!!'.PHP_EOL);throw $e; die("Exception");}
                }
    }

}
