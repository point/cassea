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

require_once("UploadedFiles.php");

class FileException extends Exception{}
// config:
// allow .. in path
// USER MODE


class FileStorageFactory{
    static function get( $storage = ''){
        switch ($storage){
        case 'video':
            $obj = new FileStorage('/movies/');
            break;
        case 'avatar':
            $obj = new FileStorage('/files/users/avatar');
            break;
        case 'image_news':
            $obj = new FileStorage('/files/');
            break;
        default: 
            $obj = new FileStorage('/');
        }
        return $obj;
    }
}

// $Id:$
//
interface iFileStorage
{
    function getFile($path);
    /*function getURL($path);*/
    function upload(UploadedFiles $u,$path = "/");
    function store($srcPath, $destPath, $moveSource = true);
    function exists($path);
    function delete($path);
    function getFiles(array $paths);
}

// {{{ FileStorage
class FileStorage implements iFileStorage{
    protected $root; // /files - путь к корню хранилища от root_dir
    protected $urlRoot; 
    //папки не содержат последнего слеша

    function __construct($urlRoot){
        $this->setFSRoot($urlRoot);
        $this->urlRoot = $urlRoot;
    }

    protected function validate($i, $isDir = true){
        $r = array('');
        foreach(explode('/', $i) as $d) if ($d != '') $r[] = $d;
        if (in_array('..', $r)) throw new FileException('Trying get back directory: '.$i);
        return implode('/', $r).($isDir?'/':'');
    }

    private function setFSRoot($urlRoot){
        $path = Config::getInstance()->root_dir.'/web/'.$urlRoot;
        $this->root = $this->validate($path, true);
        if (!is_dir($this->root)) 
            throw new FileException('Storage not exists. Given root id '.$this->root);
    }

    function getFile($path){
        return new File( $this->validate($this->root.$path, false), $this);
    }

    function getFiles(array $paths){
         foreach($paths as $p)
            $r[] = $this->getFile($p);  
    }

    function getURL($path){
        if( strpos($path, $this->root) === 0) return substr($path,strlen($this->root) -strlen($this->urlRoot));
        throw new FileException('Bad Path');
    }

    
    function exists($path){
        return is_file($this->validate($path, false));
    }

    function delete($path){
        if (!is_file($path)) return true;
        //var_dump($path);
        $c = chmod($path, 0666);
        //var_dump('chown', $c);
        $u = unlink($path);
        //var_dump('unlicnk', $u);
        return  $u;
    }
    function deleteFile(iFile $file)
    {
        $this->delete($file->getPath());
    }

    /**
     *
     *
     * @throw FileException - в случае неудачной заливки
     */
    function upload( UploadedFiles $u, $path = "/"){
        $upath = $this->validate($this->root.$path);
        $res = array();
        $uploadedFiles = $u->getUploaded();
        foreach ($uploadedFiles as $uf){
            //no need to additional check. Data in UploadedFile already checked.
            //if (!is_uploaded_file($uf['tmp_name'])) throw new FileException('File "'.$uf['tmp_name'].'" wasn\'t uploaded via HTTP POST');
            $storePath = $upath.$uf['name'];
            move_uploaded_file($uf['tmp_name'], $storePath);
            //$this->store($uf['tmp_name'], $storePath); 
            $res[] = new File($storePath, $this);
        }
        return $res;
    }

    function store($srcPath, $destFile, $moveSource = true ){
        /*
        $destDir = dirname($destFile);
        if (!$this->mkdir($destDir))
            throw new FileException('Storing file: directory "'.$dir.'" is not writable ');
         */
        if ($moveSource) $func = 'rename';
		else $func = 'copy';
		//print_pre($func);
		//print_pre($tmp_name);
		//print_pre($destFile);
		//$old = umask(octdec("0012"));
		//print_pre('Old mask '.$old);
		$res = $func($srcPath, $destFile);
        if (!$res)
            throw new FileException('Storing file: file  "'.$destFile.'" not uploaded');
        $mode = chmod ($destFile, 0666);
    }

    // returns array['dirs'] of (string)dirs and array['files'] of (File)files
    function ls($path = null)
    {
        $ret = array();
        if(!isset($path)) $path = ".";
        $this->path = trim($this->validate($path,false),"/");
        $this->root = rtrim($this->root,"/");

        if(!file_exists($this->root."/".$this->path) || !is_dir($this->root."/".$this->path)) 
            throw new FileException("Directory {$this->path} not found");

        foreach(scandir($this->root."/".$this->path) as $v)
            if($v == "." || $v == "..") continue;
            else
                if(is_file($this->root."/".$this->path."/".$v))
                    $ret['files'][] = new File($this->root."/".$this->path."/".$v,$this);
                else 
                    $ret['dirs'][] = $v;

        return $ret;
    }
    // make dir under $this->root
    function mkdir($dirname)
    {
        if(!isset($dirname) || $dirname == "." || $dirname == "..") return; 
        $dirname = str_replace(array("/","\\"),array(),$dirname);
        if(!is_dir($this->root)) return;
        if(file_exists($this->root."/".$dirname)) return;

        mkdir($this->root."/".$dirname);
    }

    // delete dir or file under $this->root
    function rm($name)
    {
        if(!isset($name) || $name == "." || $name == "..") return; 
        $name = str_replace(array("/","\\"),array(),$name);
        if(!is_dir($this->root)) return;
        if(!file_exists($this->root."/".$name)) return;

        if(is_file($this->root."/".$name))
            unlink($this->root."/".$name);
        else deltree($this->root."/".$name);
    }
}//}}}


interface iFile {}
// {{{ File
class File implements iFile{
    protected $path;
    protected $URL = null;
    protected $storage;

    function __construct($path, iFileStorage $storage){
        $this->path = $path;
        $this->storage = $storage;
    }

    public function getStorage(){
        return $this->storage;
    }
    
    public function getPath(){return $this->path;}

    function getName()
    {return basename($this->path); }

    public function getURL(){
        if (is_null($this->URL)) $this->URL = $this->getStorage()->getURL($this->getPath());
        return $this->URL;
    }
    
    public function exists(){
        return $this->getStorage()->exists($this->getPath());
    }

    public function delete(){
        $r = $this->getStorage()->delete($this->getPath());
        $this->storage = null;
        return $r;
    }
}// }}}

// {{{ Decorator
class Decorator /*extends File*/ implements iFile{
    protected $file;

    public function __construct ( iFile  $file){
        $this->file = $file;
    }
    public function setFile($file){
        if ($file instanceof Decorator)
            $this->file->setFile($file);
        else $this->file = $file;
    }

    /*public function getPath(){
        return $this->file->getPath();
    }*/

/*    public function getAbsolutePath(){
        return $this->getStorage()->getAbsolutePath($this->getPath());
    }
 */
    public function getStorage(){
        return $this->file->getStorage();
    }

    public function __call($method, $arguments){
        if (count($arguments)) $param ="'".implode("', ", $arguments)."'";
        else $param = ''; 
        $proxy = create_function('$o, $m', 'return  $o->$m('.$param.');');
        return $proxy($this->file, $method);
    }
}// }}}

// {{{ ImageDecorator
class ImageDecorator extends Decorator{
    private $height;
    private $width;
    private $type;

    function __construct(iFile $f)
    {
        parent::__construct($f);
        $this->getImageSize();
    }
    public function getHeight(){ return $this->height;}
    
    public function getWidth(){return $this->width;}

    // {{resize
    /**
     *
     * @param string $targetFile
     * @param int $maxWidth
     * @param int $maxHeight
     * @paran int $quality
     * @return resource 
     */
    public function resize($targetFile, $maxWidth, $maxHeight, $quality = 100){
        // do it in __construct
        // Needed here
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
        $image = $this->createImage($this->getPath());
        $image_p = imagecreatetruecolor($tw, $th);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tw, $th, $this->width, $this->height);
        //$tmpFile = tempnam( sys_get_temp_dir(), 'resize');
        $tmpFile = $this->getPath().'.tmp';
        imagejpeg($image_p, $tmpFile, $quality);
        $this->getStorage()->store($tmpFile, $targetFile);
        imagedestroy($image_p);
        $this->width = $tw;
        $this->height = $th;
        $this->type = IMAGETYPE_JPEG;

        return true;
    }


    private function getImageSize(){
        list($this->width, $this->height, $this->type) = getimagesize($this->getPath());
    }    

    private function createImage($path)
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
        return $functions[$this->type]($path);
    }

}// }}}

// {{{ MimeDecorator
class MimeDecorator extends Decorator{
    private $mime = null;
    public function setFile($file){
        $this->file->setFile($file);

    }
    public function getMime(){
        if(!extension_loaded('fileinfo') && !@dl('fileinfo')) return null;       
        $afile = $this->getPath();
        $finfo = new finfo( FILEINFO_MIME );
        if (!$finfo) return null;
        //var_dump(is_file($afile));
        $mime = $finfo->file($afile);
        return $mime;
    }
}// }}}

// {{{ StatDecorator
class StatDecorator extends Decorator{
    public function stat(){
        $ss=@stat($this->getPath());
        if(!$ss) return null; //Couldnt stat file
        return $ss;
    }
}// }}}
