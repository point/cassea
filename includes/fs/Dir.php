<?php
/*- vim:noet:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008,2009 Cassea Project
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*     * Redistributions of source code must retain the above copyright
*       notice, this list of conditions and the following disclaimer.
*     * Redistributions in binary form must reproduce the above copyright
*       notice, this list of conditions and the following disclaimer in the
*       documentation and/or other materials provided with the distribution.
*     * Neither the name of the Cassea Project nor the
*       names of its contributors may be used to endorse or promote products
*       derived from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY CASSEA PROJECT ''AS IS'' AND ANY
* EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL CASSEA PROJECT BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
}}} -*/

// $Id:$

 // {{{ Dir
/**
 *
 *
 *
 */
class Dir extends FileSystemObject implements iDir{
    const LS_BOTH = 0;
    const LS_FILE = 1;
    const LS_DIR = 2; 

    const MODE = 0777;

    // {{{ get
    /**
     * Фабричный метод создания директории
     *
     * @param string path
     * @return Dir
     */
    public static function get($path, $absPath = false){
        return new Dir($path, $absPath);
    }// }}}

    // {{{ getFile
    /**
     * Фабричный метод для получения файла(ов) в директории 
     * по имени относительно директории.
     * 
     * Сущестование файлов не проверятся, так же как и существования самой директории.
     * 
     * Если передать несколько имен файлов в качестве параметров,
     * Функция вернет массив и объектов File.
     *
     * Если имя файла не проходит проверку, выбрасывается FileSystemException.
     *
     * @throws FileSystemException
     * @return mixed array or File
     */
    public function getFile($filename){
        if ( ($ac = func_num_args()) == 0) return null;
        if ( $ac == 1) return new File( $this->concat($this->path, $filename), true);
        $files = array();
        $names = func_get_args();
        for($i =0; $i < $ac; $i++)
            $files[] = new File( $this->concat($this->path, $names[$i]), true);
        return $files;
    }// }}}

    // {{{ getFiles
    /** 
     * Создает объекты File по путям относительно текущей директории.
     * Следит за правильностью формирования пути.
     *
     * @param array of strings - file names
     * @throw FileSystemException в случае некорректоного пути одного из файлов
     * @return File
     */
    public function getFiles($filenames){
        if (!is_array($filenames) || !count($filenames) ) return null;
        return call_user_func_array(array($this,'getFile'),$filenames);
    }// }}}

    // {{{  getDir
    /**
     *
     */
    public function getDir($name){
        return Dir::get($this->concat( $this->path,$name), true);
    }// }}}

     // {{{ ls
    /**
     * Список файлов и директорий.
     *
     * $pattern может содержать директории: images/*.txt.
     * 
     * По умолчанию функция возвращает список фалов.
     * Это поведение можно изменить, вередав второму параметру $flag значения
     * Dir::LS_DIR - для получения только директорий или 
     * DIR::LS_BOTH - для получению файлов и директорий.
     * Параметр по умолчанию - Dir::LS_FILE.
     *
     *
     * Параметр $gflag служит для передачи опций напряму функции glob.
     * Например GLOB_NOSORT или GLOB_ERR.
     *
     * @return array of FileSystemObject
     */
    public function ls($pattern = null, $flag = Dir::LS_FILE, $gflag = 0){
        if (is_null($pattern)) $pattern = '*';
        $gflag |= GLOB_MARK;
        if ($flag == Dir::LS_DIR) $gflag |= GLOB_ONLYDIR;
        $list = glob( $this->concat($this->path,$pattern), $gflag);
        $res = array();
        foreach ( (is_array($list)?$list:array()) as $fname ){
            $fname = substr($fname,strlen($this->path));
            $isDir = substr($fname,-1) =='/';
            if (($flag == Dir::LS_DIR || $flag == Dir::LS_BOTH) &&  $isDir) $res[] = new Dir( $this->concat( $this->path , $fname), true); 
            elseif(!$isDir)
                $res[] = new File( $this->concat( $this->path , $fname), true);
        }
        return $res;

    }// }}}

    // {{{ mkdir
    /**
     * Создание директорий
     *
     * Создает директрорию(рекрсивно) с маской Dir::MODE.
     * В случае успеха возвращает объект вновь созданной директории Dir.
     *
     * Функция принимает произвольное количество параметров, каждый из которых
     * трактуется как директорию, которую необходимо создать.
     * Если параметров больше чем два функция возвращает массив созданных объектов.
     *
     * В случае если одна из директорий или поддиректорий не создана, функция выбрасывает исключение.
     * 
     * В реализации умышленно не используется параметре $recursive встроенной фукции PHP mkdir().
     *
     * <code>
     *      $dirs = mkdir('non_existent_dir/dir1', 'dir2');
     * </code>
     *
     * @throws FileSystemException
     * @return Dir, Array(Dir) 
     */
    public function mkdir(){
        if (($fnc = func_num_args()) == 0) return null;
        $res = array();
        foreach(func_get_args() as $dir){
            if(is_null($dir) || empty($dir)) throw new FileSystemException('Directory name is empty or not set.');
			$part = explode('/', $this->concat($dir));
            if ( ($c = count($part)) > 0){
                for($i = 0, $p = $this; $i < $c; $i++)
					if(!file_exists($p = $p->getDir($part[$i])))
						if (!mkdir($p, Dir::MODE)) throw new FileSystemException('Create directory fail. Dir: '.$p);
                $res[] = $this->getDir($dir);
            }
            else throw new FileSystemException('Incorrect directory name: '.$dir);
        }
        return $fnc==1?$res[0]:$res;
    }// }}}

    // {{{ upload
    /**
     * Сохраняет файлы из UploadedFiles в директорию.
     *
     * Возвращает массив загруженных файлов.
     *
     * @throws FileSystemException если перемещение файлов прошло неуспешно.
     * @return array (File)
     */
    public function upload(UploadedFiles $uf){
        $res = array();
        foreach($uf->getUploaded() as $f)
            if (move_uploaded_file($f['tmp_name'], ($f = $this->getFile($f['name']))))  $res[] = $f;
            else throw new FileSystemException('Fail in move_uploaded_file: '.$f['name']);
        return $res;
    }// }}}

    // {{{ copy
    /**
     * Рекурсивное копирование директории.
     *
     *
     *
     */
    public function copy(iDir $target, $createDirectory = true){
        if (!$target->canWrite()) throw new FileSystemException('Copy: target directory isnt writable: '.$target);
        if ($createDirectory) $target = $target->mkdir($this->getName());

        $list = $this->ls(null,Dir::LS_BOTH, GLOB_NOSORT);
        foreach($list as $o)
            //$name = $o->getName();
            //print_pre('>>> '.(is_dir($o)?" D ":"").$name);
            if ($o  instanceof File)
                $o->copy($target->getFile($o->getName()));
            elseif ($o instanceof Dir)
                $o->copy($target, true);
            else throw new FileSystemException('Unsupported object class: '.get_class($o) );
        return $target;
    }// }}}

    // {{{ rename
    /**
     * Переименование директории.
     *
     * Если в качестве параметра указана строка, не содержащая '/',
     * то происходит переименование директории.
     * Если переданная строка содержит '/' то она воспринимается
     * как путь для переименования(относительно коння хранилищиа{@link FileSystemObject::$root});
     *
     * @throws FileSystemException
     * @return iDir объект перемещенной директории
     */
    public function rename($target){
        if( !($target instanceof iDir)) 
            if( strpos($this->concat($target), '/') === false  ) $target = $this->getParent()->getDir($target);
            else $target = new Dir($target);

        $this->verifySourceTarget($this->getParent());
        if(!rename($this, $target))
            throw new FileSystemException('Unable move directory '.$this.' to '.$target);
        $this->path = ''.$target;
        return $this;
    }// }}}

    // {{{ move
    /**
     * Перемещает директорию.
     *
     * @throws FileSystemException
     * @return Dir объект перемещенной директории
     */
    public function move(iDir $target){
        $this->verifySourceTarget($target);
        if(!rename($this, ($tDir = $target->getDir($this->getName()))))
            throw new FileSystemException('Unable move directory '.$this.' to '.$target); 
        $this->path = ''.$tDir;
        return $this;
    }// }}}

     // {{{ verifySourceTarget
    /**
     * Проверяет существование директории и возможность записи в директорию $target.
     *
     * @param Dir $target
     */
    private function verifySourceTarget($target){
        if (!$this->exists()) throw new FileSystemException('Source directory not exists. Directory: '.$this);

        if ($this->isParentOf($target)) throw new FileSystemException('Source directory '.$this.' is parent of target directory '.$target);       

        if ($target instanceof Dir){  
            if (!is_dir($target)) throw new FileSystemException('Expecting directory as target. Given:'.$target.'. Is directory exist?');
        }
        else throw new FileSystemException('Function operate only with instances of class Dir');

        if (!$target->exists()) throw new FileSystemException('Target dir not exists. Dir:'.$target);
        if (!$target->canWrite()) throw new FileSystemException('Target dir not writable. Dir:'.$target);
    }// }}}

    // {{{ isParentOf
    /**
     * Возвращает true если объект $o является дочерним по дереву каталогов.
     *
     * @return bool
     */
    protected function isParentOf(iFileSystemObject $o){
        return  substr($o, 0, strlen($this->path)) == $this->path;
    }// }}}

    // {{{ delete
    /**
     * Рекурсивно удаляет директорию.
     *
     * @throws  FileSystemException если один их файлов или поддиректорий не был удален.
     * @return bool true on success, false on failure
     */
    public function delete(){
        $list = $this->ls(null, Dir::LS_BOTH, GLOB_NOSORT);
        foreach($list as $o)
            if (!$o->delete()) throw new FileSystemException('Can\'t delete '.get_class($o).' '.$o);
        $r = rmdir($this);
        $this->path= null;
        return $r;
    }// }}}
}// }}}
