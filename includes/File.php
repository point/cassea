<?php
require_once("UploadedFiles.php");

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

class FileSystemException extends CasseaException{}

// {{{ Interfaces 
interface iFileSystemObject{

    public function getName();
    public function getPath();
    public function getAbsPath();
    public function exists();
    public function canWrite();
    public function __toString();

}

interface iFile extends iFileSystemObject{
    public function getUrl();
    public function size();
    public function copy(iFileSystemObject $target);
    public function move(iFileSystemObject $target);
    public function delete();
}

interface iDir extends iFileSystemObject{
    public function getFile($filename);
    public function getDir($name);
    public function ls($pattern = null, $flag = Dir::LS_FILE, $gflag = 0);
    public function mkdir();
    public function upload(UploadedFiles $uf);
    public function copy(iDir $target, $createDirectory = true);
    public function move(iDir $target);
    public function rename(iDir $target);
    public function delete();
}// }}}

// {{{ FileSystemObject
class FileSystemObject implements iFileSystemObject{
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

    // {{{
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
    public function setRoot($root){
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
        return substr(dirname($this->path),strlen(self::$root));
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
     * соединяет переданные части пути в один.
     * @return string
     */
    protected function concat(){
        $args = array();
        foreach(func_get_args() as $k) if (!is_null($k) && !empty($k)) $args[] = $k;
        $path = implode('/', $args);
        $path = preg_replace('#/{2,}#', '/', $path);
        return $this->checkPath(rtrim($path, '/'));
    }// }}}

    // {{{ checkPath
    /**
     * Проверяет наличее ".","..".
     *
     * TODO проверять полученный путь 
     * на совместимость с файловой системой.
     *
     * @throw FileSystemException
     * @return string 
     */
    private function checkPath($path){
        $c = explode('/', $path);
        if (in_array('.', $c) || in_array('..', $c))
            throw new FileSystemException('Incorrect path: '. $path);
        return $path;
    }// }}}

}// }}}

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

    // {{{ move (rename)
    /**
     * Перемещение(переименование) файла
     *
     * Если $target - директрия, то файл будет перемещен в нутрь папки
     * в тем же именем.
     *
     * Если $target - файла произойдет переименование
     * TODO Модифицировать объект, а возвращать результат работы rename
     *
     * @throws FileSystemException
     * @return File объект перемещенного файла.
     */
    public function move(iFileSystemObject $target){
        if (!rename($this, ($tFile = $this->verifySourceTarget($target))))
            throw new FileSystemException('Unable move file '.$this.' to '.$tFile);
        $this->path = ''.$tFile;
        return true;
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
            $td = new Dir($target->getPath());
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
        //if (!$this->canWrite()) return false;
        ////throw new FileSystemException('Unable delete file. there is no write permission:'.$target); 
        return  unlink($this->path);
    }// }}}
}// }}}

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
        foreach ( $list as $fname ){
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
                for($i = 0, $p = $this->path; $i < $c; $i++)
                    if(!file_exists($p = $this->concat($p,$part[$i])))
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
            if (move_uploaded_file($f['tmp_name'], $this->concat($this, $f['name'])))  $res[] = $this->getFile($f['name']);
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
     * @throws FileSystemException
     * @return Dir объект перемещенной директории
     */
    public function rename(iDir $target){
        $this->verifySourceTarget($target);
        if(!rename($this, $target))
            throw new FileSystemException('Unable move directory '.$this.' to '.$target);
        $this->path = ''.$tDir;
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
        if(!rename($this, ($tDir = new Dir($this->concat($target, $this->getName()),true))))
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
        else throw new FileSystemException('Function operate obly with instances of class Dir');

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
        return rmdir($this);
    }// }}}
}// }}}

// {{{ TempFile
/**
 *
 */
class TempFile extends File{
    // {{{ __construct
    /**
     *
     * @param string $path
     * @param string $prefix 
     */
    public function __construct($path = null,$prefix = 'cassea_'){
        if (is_null($path)) $path = sys_get_temp_dir();
        if (!is_dir($path)) throw new FileSystemException('Temporary directory not exists: '.$path);
        parent::__construct(tempnam($path, $prefix), true);
    }// }}}

    // {{{ __destruct
    /** 
     * Удаляет временный файл
     */
    public function __destruct(){
        $this->delete();
    }// }}}
}// }}}

class DecoratorException extends CasseaException{}

// {{{ Decorator
class Decorator /*implements iFile */{
    protected $file;
    
    public function __construct ( $file){
        if ($file instanceof iFile || $file instanceof Decorator)
            $this->file = $file;
        else throw DecoratorException('$file parameter in Decortor contructoe must be instance of iFile or Decorator');
    }

    public function __toString(){
        return ''.$this->file;
    }

    public function __call($method, $arguments){
        //print_pre(get_class($this).' '.$method);
        $r= call_user_func_array(array($this->file, $method), $arguments);
        return $r;
    }

}// }}}

// {{{ ImageDecorator
class ImageDecorator extends Decorator{
    private $height;
    private $width;
    private $type;

    function __construct($f)
    {
        parent::__construct($f);
        $this->getImageSize();
    }
    
    public function getHeight(){ return $this->height;}
    public function getWidth(){return $this->width;}

    // {{{ resize
    /**
     * Изменения размеров, качества изображения.
     *
     * Изменяет размеры изображения так, чтобы они не првышали переданные $maxHeight и $maxWidth.
     * Качество устанавливается переменной $quality, которая принимет значения от 1 до 100;
     *
     * Вновь созданное(конвертированное) изображение сохраняется в объект $targetFile.
     * Если указанно $targetFile = null изображение сохраняется вместо оригинала.
     *
     * Функция возвращает измененное изображение.
     *
     * @throws DecoratorException
     * @param string $targetFile
     * @param int $maxWidth
     * @param int $maxHeight
     * @paran int $quality
     * @return iFile 
     */
    public function resize($targetFile = null, $maxWidth, $maxHeight, $quality = 100){
        if ($quality <1 || $quality >100) throw new DecoratorException('Quality must be integer from 1 to 100, but '.$quality.' given.');
        if (is_null($targetFile)) $targetFile = $this;
        $this->getImageSize();
        $proportion = $this->width / $this->height;
        if($this->width >= $this->height)
        {
            $tw = $maxWidth;
            $th = (int)round($tw / $proportion);
        }
        else{
            $th = $maxHeight;
            $tw = (int)round( $proportion * $th);
        } 
        $image = $this->createImage();
        $image_p = imagecreatetruecolor($tw, $th);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tw, $th, $this->width, $this->height);
        $tmpFile = new TempFile();
        imagejpeg($image_p, $tmpFile, $quality);
        imagedestroy($image_p);
        $tmpFile->move($targetFile);
        if ($targetFile instanceof ImageDecorator){
            $targetFile->width = $tw;
            $targetFile->height = $th;
            $targetFile->type = IMAGETYPE_JPEG;
        }
        return $targetFile;
    }// }}}

    // {{{ getImageSize
    /** 
     * Устанавливает объекту значения ширины, высоты и типа изображения.
     * 
     * @throws DecoratorException если файл не существует или не является изображение.
     * @return bool true 
     */
    private function getImageSize(){
        if (!$this->file->exists())
            throw new DecoratorException('File '.$this->file.' not exists.');
        if( ($prperties = @getimagesize($this->file)) ===false )
            throw new DecoratorException('File '.$this->file.' not image.');
        $this->width = $prperties[0];
        $this->height = $prperties[1];
        $this->type = $prperties[2];
        return true;
    }// }}}    

    // {{{ createImage
    /**
     * Фабричный метод. Создает изображение в зависимоти от типа файла
     *
     * Тонкости реализации GD
     *
     * @return resource
     */
    private function createImage()
    {
        if(!$this->type)  return false;
        $functions = array(
            IMAGETYPE_GIF => 'imagecreatefromgif',
            IMAGETYPE_JPEG => 'imagecreatefromjpeg',
            IMAGETYPE_PNG => 'imagecreatefrompng',
            IMAGETYPE_WBMP => 'imagecreatefromwbmp',
            IMAGETYPE_XBM => 'imagecreatefromwxbm',
        );
        if(!isset($functions[$this->type]) || !function_exists($functions[$this->type]))return false;
        return $functions[$this->type]($this);
    }// }}}
}// }}}

// {{{ MimeDecorator
class MimeDecorator extends Decorator{
    public function getMime(){
        if(!extension_loaded('fileinfo') && !@dl('fileinfo')) return null;       
        $finfo = new finfo( FILEINFO_MIME );
        if (!$finfo) return null;
        //var_dump(is_file($afile));
        $mime = $finfo->file($this);
        return $mime;
    }
}// }}}

// {{{ StatDecorator
class StatDecorator extends Decorator{
    public function stat(){
        $ss=@stat($this);
        if(!$ss) return null; //Couldnt stat file
        return $ss;
    }
}// }}}

// @deprecated 
class FileStorage extends Dir{

	public function upload(UploadedFiles $uf, $path ='/'){
		throw new FileSystemException("FileStorage::upload() is deprecated. Use Dir::upload instead");
	}
}
