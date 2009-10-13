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

 
// $Id$
//
class UploadedFiles
{
	const ERROR_SEPARATOR = "<br/>";
    protected 
        $http_files = array(),
        $http_error_files = array()
        ;
    private
        $cached_extensions = array(),
        $cached_mimes = array()
        ;
    static 
            $compressed_mimes = array(
                'application/x-tar'=>null,
                'application/x-cpio'=>null,
                'application/x-debian-package'=>null,
                'application/x-archive'=>null,
                'application/x-arc'=>null,
                'application/x-arj'=>null,
                'application/x-lharc'=>null,
                'application/x-lha'=>null,
                'application/x-rar'=>null,
                'application/zip'=>null,
                'application/zoo'=>null,
                'application/x-eet'=>null,
                'application/x-java-pack200'=>null,
                'application/x-compress'=>null,
                'application/x-gzip'=>null,
                'application/x-bzip2'=>null
            );
    function __construct($names = null)
    {
        if (is_null($names)) $httpFiles = $_FILES;
        else $httpFiles = array_intersect_key($_FILES, array_flip(is_scalar($names)? array($names): $names));
        foreach($httpFiles as $name => $v)
            if(is_scalar($v['error']))
                if($v['error']  === UPLOAD_ERR_OK && is_uploaded_file($v['tmp_name']) && $v['size'] !== 0)
                    $this->http_files[$name] = $v;
                else
					$this->http_error_files[$name][] = array($v['error']); 
			elseif(is_array($v['error']))
				foreach($v['error'] as $key => $error)
                    if(is_scalar($error) && $error === UPLOAD_ERR_OK && is_uploaded_file($v['tmp_name'][$key]) && $v['size'][$key] !== 0 )
                        $this->http_files["{$name}\\{$key}"] = 
                            array("name"=>$v["name"][$key],
                                "type"=>$v['type'][$key],
                                "size"=>$v['size'][$key],
                                "tmp_name"=>$v['tmp_name'][$key],
                                "error"=>$error);
                    else
                        $this->http_error_files["{$name}\\{$key}"][] =array($error); 
    

    }
    function isEmpty()
    {
        return empty($this->http_files);
    }
	function getCount()
	{
	   return count($this->http_files);	
	}
    function getFileRaw($w_name,$additional_id = null)
    {
        $name = isset($additional_id)?$w_name."\\".$additional_id:$w_name;
        return (isset($this->http_files[$name]))?$this->http_files[$name]:null;
    }
    function setFileName($newFileName, $w_name, $additional_id = null)
    {
        $name = isset($additional_id)?$w_name."\\".$additional_id:$w_name;
        if (isset($this->http_files[$name])) $this->http_files[$name]['name'] = $newFileName;

        return (isset($this->http_error_files[$name]))?$this->http_error_files[$name]:null;

    }

    function getUploaded(){
        $uploaded = array();
        foreach($this->http_files as $name => $info)
            if (!isset($this->http_error_files[$name])) $uploaded[] = $info;
        return $uploaded;
    }

	// {{{ isUploaded
	/**
	 * Проверяет пытался ли пользователь загрузить 
	 * файл или оставил поле для выбора с файлом пустым
	 *
	 * Необходима для обработки необзательных полей WFile.
	 *
	 * <code>
	 * ...
	 *
	 * function imageChecker($post){
	 *		$uf = new UploadedFiles('image');
	 *		if (!$uf->isUploaded('image')) return; // ползователь не выбрал изображение
	 *
	 *      $uf->allowedMimesLike('image/')->allowedSize(0, 4000*1024)->count(1);
	 *      if ($uf->hasErrors()) throw new CheckerException ($uf->getErrorsFor('image'), 'image');
	 * }
	 * </code>
	 *
	 * @param string $w_name 
	 * @param string $additional_id
	 * @param bool 
	 */
	function isUploaded($w_name, $additional_id=null){
		$name = isset($additional_id)?$w_name."\\".$additional_id:$w_name;
		return  ( !isset( $this->http_error_files[$name][0][0]) ||  $this->http_error_files[$name][0][0] != UPLOAD_ERR_NO_FILE );
	}

    function hasErrors()
    {
        return !empty($this->http_error_files);
    }
    function getErrorsFor($w_name, $additional_id = null)
    {
		$name = isset($additional_id)?$w_name."\\".$additional_id:$w_name;
		if (!isset($this->http_error_files[$name])) return null;
		$err = array_map(create_function('$a', 'return  call_user_func_array("Language::message", $a);'), 
			array_map(create_function('$e', 'array_unshift($e,"upload");return $e;'), $this->http_error_files[$name]));
		return implode(UploadedFiles::ERROR_SEPARATOR, $err);
    }
    function maxCount($max_count)
    {
        if(count($this->http_files) > $max_count) 
            $this->http_files = array();
        return $this;
    }
    function count($count)
    {
        if(count($this->http_files) != $count)
            $this->http_files = array();
        return $this;
    }
    function excludeExtensions($e_ext)
    {
		if(is_scalar($e_ext))
            $e_ext = array($e_ext);
        $e_ext = array_flip(array_map(create_function('$e','if($e{0} !== ".") return ".".$e; else return $e;'),$e_ext));

        foreach($this->http_files as $k => $v)
        {
            $ext = isset($this->cached_extensions[$k]) ? $this->cached_extensions[$k] : ($this->cached_extensions[$k] = strrchr($v['name'],'.'));
            if(!empty($e_ext) && isset($e_ext[$ext]))
                $this->http_error_files[$k][] = array('extension', $v['name']);
        }
        return $this;
    }
    function allowedExtensions($a_ext)
    {
		if(is_scalar($a_ext))
            $a_ext = array($a_ext);
        $a_ext = array_flip(array_map(create_function('$e','if($e{0} !== ".") return ".".$e; return $e;'),$a_ext));

        foreach($this->http_files as $k => $v)
        {
            $ext = isset($this->cached_extensions[$k]) ? $this->cached_extensions[$k] : ($this->cached_extensions[$k] = strrchr($v['name'],'.'));
            if(!empty($a_ext) && !isset($a_ext[$ext]))
                $this->http_error_files[$k][] = array('extension',$v['name']);
        }
        return $this;
    }
    function excludeMimes($e_mimes)
    {
        if(is_scalar($e_mimes))
            $e_mimes = array($e_mimes);
        $e_mimes = array_flip($e_mimes);

        foreach($this->http_files as $k => $v)
        {
            $mime = isset($this->cached_mimes[$k]) ? $this->cached_mimes[$k] : ($this->cached_mimes[$k] = getMime($v['tmp_name']));
            if($mime && isset($e_mimes[$mime]))
                $this->http_error_files[$k][] =array('mime',$v['name']);
        }
        return $this;
    }
    function allowedMimes($a_mimes)
    {
        if(is_scalar($a_mimes))
            $a_mimes = array($a_mimes);
        $a_mimes = array_flip($a_mimes);

        foreach($this->http_files as $k => $v)
        {
            $mime = isset($this->cached_mimes[$k]) ? $this->cached_mimes[$k] : ($this->cached_mimes[$k] = getMime($v['tmp_name']));

            if($mime && !isset($a_mimes[$mime]))
                $this->http_error_files[$k][] = array('mime',$v['name']);
        }
        return $this;
    }
    function excludeMimesLike($a_mimes)
    {
        if(is_scalar($a_mimes)) $a_mimes = array($a_mimes);

        foreach($this->http_files as $k => $v)
        {
            $mime = isset($this->cached_mimes[$k]) ? $this->cached_mimes[$k] : ($this->cached_mimes[$k] = getMime($v['tmp_name']));

			$matched = false;
			foreach($a_mimes as $m)
				if ( ($pos =  strpos($mime, $m)) !== false && $pos == 0) $matched =  true;
            if($mime && $matched)
				$this->http_error_files[$k][] = array('mime',$v['name']);
		}
        return $this;
    }
    function allowedMimesLike($a_mimes)
    {
        if(is_scalar($a_mimes)) $a_mimes = array($a_mimes);

        foreach($this->http_files as $k => $v)
        {
            $mime = isset($this->cached_mimes[$k]) ? $this->cached_mimes[$k] : ($this->cached_mimes[$k] = getMime($v['tmp_name']));

			$matched = false;
			foreach($a_mimes as $m)
				if ( ($pos =  strpos($mime, $m)) !== false && $pos == 0) $matched =  true;
            if($mime && !$matched)
				$this->http_error_files[$k][] = array('mime',$v['name']);
		}
        return $this;
    }
    function allowedSize($min = null, $max = null)
    {
        if($min === null && $max === null) return $this;
        $min = sizeFromString($min);
		$max = sizeFromString($max);

        foreach($this->http_files as $k => $v)
        {
            $size = sprintf("%u", @filesize($v['tmp_name']));
            if($min && $size < $min)
                $this->http_error_files[$k][] = array('min_size',$v['name'],sizeToString($min),$size);
			elseif($max && $size > $max)
                $this->http_error_files[$k][] = array('max_size' ,$v['name'],sizeToString($max),$size);
        }
        return $this;
    }
    function allowedImageSize($min_height = null, $max_height = null, $min_width = null, $max_width = null)
    {
        if(!isset($min_height,$max_height,$min_width,$max_width)) return;
        
        $min_height = abs($min_height);$max_height = abs($max_height);
        $min_width = abs($min_width);$max_width = abs($max_width);

        if($max_height - $min_height <= 0) return $this;
        if($max_width - $min_width <= 0) return $this;

        foreach($this->http_files as $k => $v)
        {
            $mime = isset($this->cached_mimes[$k]) ? $this->cached_mimes[$k] : ($this->cached_mimes[$k] = getMime($v['tmp_name']));
            if(($pos = strpos($mime,"image")) !== false && $pos == 0)
            {
                $s = getImgSizeNoCache($v['tmp_name']);
                if($min_height && $s['height'] < $min_height)
                    $this->http_error_files[$k][] = array('min_height',$v['name'],$min_height,$s['height']);
				elseif($max_height && $s['height'] > $max_height)
                    $this->http_error_files[$k][] = array('max_height',$v['name'],$max_height,$s['height']);

                if($min_width && $s['width'] < $min_width)
                    $this->http_error_files[$k][] = array('min_width',$v['name'],$min_height,$s['width']);
				elseif($max_width && $s['width'] > $max_width)
                    $this->http_error_files[$k][] = array('max_width',$v['name'],$max_height,$s['width']);
            }
        }
        return $this;
    }
    function onlyCompressed()
    {
        foreach($this->http_files as $k => $v)
        {
            $mime = isset($this->cached_mimes[$k]) ? $this->cached_mimes[$k] : ($this->cached_mimes[$k] = getMime($v['tmp_name']));
            if(!isset(self::$compressed_mimes[$mime]))
                $this->http_error_files[$k][] = array('compressed',$v['name']);
        }
        return $this;
    }
    function onlyImages()
    {
        foreach($this->http_files as $k => $v)
        {
            $mime = isset($this->cached_mimes[$k]) ? $this->cached_mimes[$k] : ($this->cached_mimes[$k] = getMime($v['tmp_name']));
            if(($pos = strpos($mime,"image")) === false || $pos != 0)
				$this->http_error_files[$k][] = array('image',$v['name']);
        }
        return $this;
    }
}
