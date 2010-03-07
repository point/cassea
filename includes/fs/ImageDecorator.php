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

//{{{ ImageDecorator
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
        /*if($this->width >= $this->height)
        {
            $tw = $maxWidth;
            $th = (int)round($tw / $proportion);
        }
        else{
            $th = $maxHeight;
            $tw = (int)round( $proportion * $th);
		}*/ 
		list($tw,$th) = recalcSize($this->width,$this->height,$maxWidth, $maxHeight);
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
}//}}}
