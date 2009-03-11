<?php
/*- vim:expandtab:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008, Cassea Project
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
//
class UploadedFiles
{
    protected 
        $http_files = array(),
        $http_error_files = array()
        ;
    private
        $cached_extensions = array(),
        $cached_mimes = array()
        ;
    static $upload_errors = array(
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.',
    );
    static $filter_errors = array(
            'extension' => "The file %s has incorrect extension.",
            'mime' => "The file %s has incorrect type.",
            'min_size' => "Minimum allowed size for file %s is %s but %s detected.",
            'max_size' => "Maximum allowed size for file %s is %s but %s detected.",
            'min_height' => "Minimum expected height for image %s should be %s but %s detected.",
            'max_height' => "Maximum allowed height for image %s should be %s nut %s detected.",
            'min_width' => "Minimum expected width for image %s should be %s but %s detected.",
            'max_width' => "Maximum allowed width for image %s should be %s nut %s detected.",
            'compressed' => "The file %s is not compressed.",
            'image' => "The file %s is not an image."
    );
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
        print_pre($httpFiles);
        foreach($httpFiles as $name => $v)
            if(is_scalar($v['error']))
                if($v['error']  === UPLOAD_ERR_OK && is_uploaded_file($v['tmp_name']) && $v['size'] !== 0)
                    $this->http_files[$name] = $v;
                else
                    $this->http_error_files[$name] = self::$upload_errors[$v['error']];
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
                        $this->http_error_files["{$name}\\{$key}"] = self::$upload_errors[$error];
    

    }
    function isEmpty()
    {
        return empty($this->http_files);
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
    function hasErrors()
    {
        return !empty($this->http_error_files);
    }
    function getErrorsFor($w_name, $additional_id = null)
    {
        $name = isset($additional_id)?$w_name."\\".$additional_id:$w_name;
        return (isset($this->http_error_files[$name]))?$this->http_error_files[$name]:null;

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
        $e_ext = array_flip(array_map(create_function('$e','if($e{0} !== ".") return ".".$e;'),$e_ext));

        foreach($this->http_files as $k => $v)
        {
            $ext = isset($this->cached_extensions[$k]) ? $this->cached_extensions[$k] : ($this->cached_extensions[$k] = strrchr($v['name'],'.'));
            /*if(isset($this->cached_extensions[$k]))
                $ext = $this->cached_extensions[$k];
        else $ext = $this->cached_extensions[$k] = strrchr($v['name'],'.');*/

            if(!empty($e_ext) && isset($e_ext[$ext]))
            {
                $this->http_error_files[$k] = sprintf(self::$filter_errors['extension'],$v['name']);
                unset($this->http_files[$k]);
            }
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
            {
                $this->http_error_files[$k] = sprintf(self::$filter_errors['mime'],$v['name']);
                unset($this->http_files[$k]);
            }
        }
        return $this;
    }
    function allowedExtensions($a_ext)
    {
        if(is_scalar($a_ext))
            $a_ext = array($a_ext);
        $a_ext = array_flip(array_map(create_function('$e','if($e{0} !== ".") return ".".$e;'),$a_ext));

        foreach($this->http_files as $k => $v)
        {
            $ext = isset($this->cached_extensions[$k]) ? $this->cached_extensions[$k] : ($this->cached_extensions[$k] = strrchr($v['name'],'.'));

            if(!empty($a_ext) && !isset($a_ext[$ext]))
            {
                $this->http_error_files[$k] = sprintf(self::$filter_errors['extension'],$v['name']);
                unset($this->http_files[$k]);
            }
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
            {
                $this->http_error_files[$k] = sprintf(self::$filter_errors['mime'],$v['name']);
                unset($this->http_files[$k]);
            }
        }
        return $this;
    }
    function allowedSize($min = null, $max = null)
    {
        if($min === null && $max === null) return $this;
        $min = $this->sizeFromString($min);
        $max = $this->sizeFromString($max);
        $min = abs($min);$max = abs($max);
        if($max - $min <= 0) return $this;

        foreach($this->http_files as $k => $v)
        {
            $size = sprintf("%u", @filesize($v['tmp_name']));
            if($min && $size < $min)
            {
                $this->http_error_files[$k] = sprintf(self::$filter_errors['min_size'],$v['name'],$min,$size);
                unset($this->http_files[$k]);
                continue;
            }
            if($max && $size > $max)
            {
                $this->http_error_files[$k] = sprintf(self::$filter_errors['max_size'],$v['name'],$max,$size);
                unset($this->http_files[$k]);
                continue;
            }
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
                {
                    $this->http_error_files[$k] = sprintf(self::$filter_errors['min_height'],$v['name'],$min_height,$s['height']);
                    unset($this->http_files[$k]);
                    continue;
                }
                if($max_height && $s['height'] > $max_height)
                {
                    $this->http_error_files[$k] = sprintf(self::$filter_errors['max_height'],$v['name'],$max_height,$s['height']);
                    unset($this->http_files[$k]);
                    continue;
                }
                if($min_width && $s['width'] < $min_width)
                {
                    $this->http_error_files[$k] = sprintf(self::$filter_errors['min_width'],$v['name'],$min_height,$s['width']);
                    unset($this->http_files[$k]);
                    continue;
                }
                if($max_width && $s['width'] > $max_width)
                {
                    $this->http_error_files[$k] = sprintf(self::$filter_errors['max_width'],$v['name'],$max_height,$s['width']);
                    unset($this->http_files[$k]);
                    continue;
                }
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
            {
                $this->http_error_files[$k] = sprintf(self::$filter_errors['compressed'],$v['name']);
                unset($this->http_files[$k]);
            }
        }
        return $this;
    }
    function onlyImages()
    {
        foreach($this->http_files as $k => $v)
        {
            $mime = isset($this->cached_mimes[$k]) ? $this->cached_mimes[$k] : ($this->cached_mimes[$k] = getMime($v['tmp_name']));
            if(($pos = strpos($mime,"image")) === false || $pos != 0)
            {
                $this->http_error_files[$k] = sprintf(self::$filter_errors['image'],$v['name']);
                unset($this->http_files[$k]);
            }
        }
        return $this;
    }

    private final function sizeFromString($size)
    {
        if (is_numeric($size)) 
            return (integer) $size;

        $size = trim($size);
        $value = substr($size, 0, -2);
        switch (strtoupper(substr($size, -2))) 
        {
            case 'YB':
                //$value *= (1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024);
                $value *= 1208925819614629174706176;
                break;
            case 'ZB':
                //$value *= (1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024);
                $value *= 1180591620717411303424;
                break;
            case 'EB':
                //$value *= (1024 * 1024 * 1024 * 1024 * 1024 * 1024);
                $value *= 1152921504606846976;
                break;
            case 'PB':
                //$value *= (1024 * 1024 * 1024 * 1024 * 1024);
                $value *= 1125899906842624;
                break;
            case 'TB':
                //$value *= (1024 * 1024 * 1024 * 1024);
                $value *= 1099511627776;
                break;
            case 'GB':
                //$value *= (1024 * 1024 * 1024);
                $value *= 1073741824;
                break;
            case 'MB':
                //$value *= (1024 * 1024);
                $value *= 1048576;
                break;
            case 'KB':
                $value *= 1024;
                break;
        }
        return $value;
    }
    private final function sizeToString($size)
    {
        $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        for ($i=0; $size >= 1024 && $i < 9; $i++) 
            $size /= 1024;
        return round($size, 2) . $sizes[$i];
    }

}
