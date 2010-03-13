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

/**
 * Библиотека для работы с файлами и директориями.
 *
 * Странности и todo: 
 * 1. ??? Удаление файла может происходить при "a-w"
 * 2. (+)Методы  move и rename должны модифицировать объект
 * 3. При рекурсивном удалении директории unset'ить объекты 
 * удаленных файлов и директорий (ref.count, gc?)
 * 4. filesize для файлов больше 2Гб
 * 5. FileSystemObject::checkPath: проверять полученный путь на совместимость с файловой системой.
 * 6. FileSystemObject::getName: проверять выход за пределы root для объектов созданных без опции $absPath.
 */

// $Id:$

// {{{ FileSystemObject
class FileSystemObject implements iFileSystemObject{
    /**
     * Массив подстановок в именах фалов и директорий.
     * Замена '\' => '/' необходима для корректной работы в Windows.
     *
     * @var array 
     */
	static $name_replacements = array(
				'\\'=> '/',
				'[' => '_',
				']' => '_'
				#'/' => '_'
			);
    /**
     * Абсолютный путь файла
     * @var string
     */
    protected $path;
    /**
     * Путь от корня файловой системы до корня хранилища файлов(web).
     * Содержит последний слэш.
     *
     * @var string
     */
    protected static $root;

    // {{{ __construct
    /**
     * Конструктор объекта файловой системы
     *
     * 
     * @param string $path
     * @param bool $absPath
     */
    public function __construct($path, $absPath = false){
        if (is_null(self::$root)) self::$root = Config::getInstance()->root_dir.'/web/';
        //if (is_null(self::$root)) self::$root = '/home/billy/work/file/';
        $this->path =($absPath)?$this->concat($path):$this->concat(self::$root, $path);
    }// }}}

    // {{{ getRoot
    /**
     *
     */
    public function getRoot(){
        return self::$root;
    }// }}}

    // {{{ setRoot
    /**
     *
     */
    static public function setRoot($root){
        self::$root = $root;
    }// }}}
    
    // {{{ getName
    /**
     * возвращает имя объекта(файла или директории)
     * @return string 
     */
    public function getName(){
        //TODO up over root
        //if (strlen($this->path) < strlen(self::$root)) return '';
        return basename($this->path);
    }// }}}

    // {{{getPath
    /**
     * Возвращает путь к объекту FileSystemObject(файлу или директории) 
     * начиная от корня web-файлов без первого слеша; 
     *
     * В случае, если объект создан с использованием абсолютного пути, 
     * функция ведет себя неадекватно. 
     * (ИМХО смысла в вызове функции для такого файла не много)
     *
     * @return string
     */
    public function getPath(){
        if (strlen($this->path) <= strlen(self::$root)) return '';
        return substr($this->getParent(),strlen(self::$root));
    }// }}}

    // {{{ getAbsPath
    /**
     * Возвращает абсолютный путь к объекту.
     *
     * @return string
     */
    public function getAbsPath(){
        return $this->path;
    }// }}}

    // {{{ getParent
    /**
     * Возвращает объект родительской директории.
     *
     * @return Dir
     */
    public function getParent(){
        return new Dir(dirname($this->path), true);
    }// }}}

    // {{{ exists
    /**
     * Проверяет существование объекта FileSystemObject
     *
     * @return bool true if object exists, false otherwise 
     */
    public function exists(){
        return file_exists($this);
    }// }}}

    // {{{ canWrite
    /**
     * Проверяет существование и возможность записи.
     * 
     * Для директории означает возможость модификации файлов внутри директории.
     *
     * false может означать что директория не существует вовсе или недостаточно 
     * прав для записи.
     *
     * @return bool 
     */
    public function canWrite(){
        return is_writable($this->path);
    }// }}}
 
    // {{{ __toString
    /**
     * Фунция возвращает абсолютный путь объекта (файла или директории).
     *
     * Данная реализация позаволяет использовать объект во встроенных
     * функциях. Например:
     * <code>
     *   $f = new File('/tmp/1.txt');
     *   $fHandler = fopen( $f, 'r+');
     * </code>  
     *
     *
     * @return string absolute path to 
     */
    public function __toString(){
        return $this->getAbsPath();
    }// }}}
    
    /** path utility functions **/
    // {{{ concat
    /**
     * Cоединяет переданные части пути в один.
     *
     * Проверяет наличее в пути ".",".." и  на совместимость с файловой системой.
     *
     * @return string
     */
    protected function concat($p){
        $path = array();
        $res = array() ;
        foreach(func_get_args() as $k) 
            if (!is_null($k) && !empty($k))
                foreach(explode('/',$k) as $d)
                    if (!empty($d))
                        if (in_array($d, array(',','..'))) throw new FileSystemException('Incorrect path: '. $path);
                        else $res[] = strtr($d, self::$name_replacements);
        
        return (substr($p,0,1) == '/'?'/':'').implode('/',$res);
    }// }}}

	// {{{ getter && settter
	// {{{ __get
	/**
	 *
	 */
	public function __get($property){
		return $this->accessor('get', $property);
	}// }}} 

	// {{{ __set
	public function __set($property, $value){
		return $this->accessor('set', $property, $value);
	}// }}}

	// {{{ accessor
	/**
	 * Check accesses if needed
	 *
	 */
	private function accessor($type, $property, $value = null){
		if (!method_exists($this, ($method = $type.ucfirst($property))))
			throw new FileSystemException('Unknown method "'.$method.'" isn\'t defined.'); 
		return $this->$method($value);
	}// }}}
	// }}}
}// }}}
