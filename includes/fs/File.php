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

// {{{ File 
class File extends FileSystemObject implements iFile{

    // {{{ get
    /**
     * Фабричный метод создания файла
     *
     * @param string path
     * @return Dir
     */
    public static function get($path, $absPath = false){
        return new File($path, $absPath);
    }// }}}

    // {{{ __construct
    /**
     * Конструктор проверяет чтобы имя файла было не пусто
     */
    public function __construct($path= null, $absPath = false){
        if (is_null($path) || $path=='') throw new FileSystemException('No path or file name given for File constructor');
        parent::__construct($path, $absPath);
    }// }}} 

	// {{{ setContent
	/**
	 *
	 */
	function setContent($content = ''){
		if (@file_put_contents($this->path, $content) === false)
			throw new FileSystemException('Cant put content to file '.$this); 
		return true;
	}// }}}


	// {{{ append
	function append($content){
		if (@file_put_contents($this->path, $content, FILE_APPEND) === false)
			throw new FileSystemException('Cant append content to file '.$this); 
		return true;
		

	}// }}}

	// {{{ getContent
	/**
	 *
	 */
	function getContent()
	{
		$r = @file_get_contents($this);
		if ($r === false)
			throw new FileSystemException('Can\'t get file contents '.$this); 
		return $r;
	}// }}}

	
    // {{{ getExtension
    /**
     * Возвращает расширение файла.
     *
     * Параметр $dots указывает сколько точек содержится в расширении.
     * Например для файла image.tmb.jpeg и $dots=2 функция вернет 'tmb.jpeg',
     * если $dots=1(по умолчанию) функция вернет 'jpeg'
     *
     * Если параметр $dots больше или равен количеству точек в имени файла,
     * функция вернет имя файла полностью.
     *
     * @return  string
     */
    public function getExtension($dots = 1){
        if ($dots == 1) return pathinfo($this->path, PATHINFO_EXTENSION);
        $c = explode('.',$this->getName());
        return implode('.',array_slice($c, -$dots));
    }// }}}

    // {{{ getUrl
    /** 
     * Возвращет URL относительно корня сайта
     *
     * Подразумевается, что self::$root указывает на корень web файлов.
     *
     * @retrun string 
     */
    public function getUrl(){
        return substr($this->path,strlen(self::$root)-1);
    }// }}}

    // {{{ size
    /**
     * Размер файла в байтах.
     *
     * Если файл не существует, функция вернет null
     * TODO filesize для файлов больше 2Гб
     *
     * @return int
     */
    public function size(){
        return $this->exists()?filesize($this->path):null;
    }// }}}
    
    // {{{ canRead
    /**
     * Проверят существование файла и возможность чтения
     *
     * @return bool if file exists and readable
     */
    public function canRead(){
        return is_readable($this->path);
    }// }}}

    // {{{ copy
    /**
     * Копирование файла
     *
     * См. File::move, File::verifySourceTarget
     *
     * @throws FileSystemException
     * @return File целевой созданный в процессе копирования
     */
    public function copy(iFileSystemObject $target){
        if (copy($this, ($tFile= $this->verifySourceTarget($target)))) return $tFile;
        throw new FileSystemException('Unable copy file '.$this.' to '.$tFile);
    }// }}}

    // {{{ rename
    /**
     * Переименование файла.
     * 
     * Если $newName строка, не содержащая '/',
     * то метод переименует файл.
     * Во всех других случаях метод проксирует вызов методу {@link File::move()}.
     *
     * @throws FileSystemException
     * @return Dir объект перемещенной директории
     */
    public function rename($newName){
        if( !($newName instanceof iFileSystemObject) && strpos($this->concat($newName), '/') === false  )  
            $newName = $this->getParent()->getFile($newName);
        return $this->move($newName);
    }
    // }}}

    // {{{ move  
    /**
     * Перемещение файла
     *
     * Если $target - директрия, то файл будет перемещен в нутрь папки
     * в тем же именем.
     *
     * Если $target - файла произойдет переименование
     *
     * @throws FileSystemException
     * @return File объект перемещенного файла.
     */
    public function move($target){
        if(!($target instanceof iFileSystemObject))
            $target = (is_dir($target))?t(new Dir($target, true)):t(new File($target, true));

        if (!rename($this, ($tFile = $this->verifySourceTarget($target))))
            throw new FileSystemException('Unable move file '.$this.' to '.$tFile);
        if (!($this instanceof TempFile))
            $this->path = ''.$tFile;
    }// }}}

    // {{{ verifySourceTarget
    /**
     * Проверка данных и подготовка копирования/перемещения файла
     *
     * Возвращает объект File пригодный для использования функциями move, rename
     *
     * Проверяет существования файл ($this),
     * возможность записи в папку назначения итд.
     *
     * @throws FileSystemException
     * @return File
     */
    private function verifySourceTarget($target){
        if (!$this->exists()) throw new FileSystemException('Source file not exists. File: '.$this);

        if ($target instanceof Dir){  
            if (is_file($target)) throw new FileSystemException('Expecting directory as target, given file:'.$target);
            $td = $target;      
            $tFile = new File($this->concat($target,$this->getName()), true);
        }
        elseif($target instanceof File){
            if (is_dir($target)) throw new FileSystemException('Expecting file as target, given directory:'.$target);
            $td = $target->getParent();
            $tFile = $target;
        }
        else throw new FileSystemException('Undefined object type');

        if (!$td->exists()) throw new FileSystemException('Target dir not exists. Dir:'.$td);
        if (!$td->canWrite()) throw new FileSystemException('Target dir not writable. Dir:'.$td);
        
        return $tFile;
    }// }}}
    
    // {{{ delete
    /**
     * Удаляет файл
     * 
     * Возвращает true если файл успешно удален или НЕ СУЩЕСТВУЕТ.
     * Результат true сигнаализирует об успешном удалении;
     *
     * @return bool
     */
    public function delete(){
        if (!$this->exists()) return true;
        $re = unlink($this->path);
        $this->path= null;
        return $re;
    }// }}}
}// }}}
