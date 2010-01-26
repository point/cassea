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
 * This file contains class for simplifying management of uploaded
 * files to server-side.
 *
 * @author billy <alexey.mirniy@gmail.com>
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

//{{{ UploadedFiles
/**
 * This class simplifies management of uploaded files. 
 * For example:
 * <pre><code>
 * $uploaded_files = t(new UploadedFiles)->count(2)->allowedExtensions(array("jpeg","jpg"))-"jpeg","jpg"))->allowedImageSize(640,480);
 * </code></pre>
 *
 * Newly created object could be passed, for example, to upload() method of Dir object.
 *
 * Note, that current implementation could handle uploaded files only with one
 * level of HTML ....
 * <code><input type="file" name="file[1][2]"/></code> will be skipped silently.
 *
 * Note, that all kind of filters (maxCount,allowedExtensions etc), will be applied only to the current object.
 * Newly created object of UploadedFiles without any filters will hold all uploaded files with only one exception:
 * files, uploaded with error (status not equal to UPLOAD_ERR_OK), would be filtered from the list.
 */
class UploadedFiles
{
    protected 
		/**
		 * Holds parameters for uploaded files
		 * @var array
		 */
		$http_files = array(),
		/**
		 * Holds info for files, uploaded with errors
		 */
        $http_error_files = array()
        ;
    private
		/**
		 * Holds info about extension of each uploaded file
		 */
		$cached_extensions = array(),
		/**
		 * Stores info about computed mime type for each uploaded file
		 */
        $cached_mimes = array()
        ;

	/**
	 * Mime types for various archives
	 */
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

	//{{{ __construct
	/**
	 * Creates an object.
	 *
	 * @param mixed could be either scalar or array with the names of HTML controls. 
	 * If passed, all uploaded files, except given, would not be take a part in current object.
	 * To take all files in consideration, you should create a new object without any parameters, passed
	 * via constructor. Note, that no physical files will be modified, deleted or moved.
	 */
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
	//}}}

	//{{{ isEmpty
	/**
	 * Determine whenever current filters chain cause empty 
	 * file list.
	 *
	 * @param null
	 * @return bool
	 * @see isUploaded
	 */
    function isEmpty()
    {
        return empty($this->http_files);
	}
	//}}}

	//{{{ getCount
	/**
	 * Returns count of files in the current list
	 *
	 * @param null
	 * @return int 
	 */
	function getCount()
	{
	   return count($this->http_files);	
	}
	//}}}

	//{{{ getFileRaw
	/**
	 * Returns all params, taken from $_FILES for given 
	 * HTML control name.
	 * Optionally, additional index of HTML element might be pointed.
	 *
	 * @param string name of HTML element
	 * @param string optional additional index
	 * @return array 
	 * @see getUploaded
	 */
    function getFileRaw($w_name,$additional_id = null)
    {
        $name = isset($additional_id)?$w_name."\\".$additional_id:$w_name;
        return (isset($this->http_files[$name]))?$this->http_files[$name]:null;
    }
	//}}}

	//{{{ setFileName
	/**
	 * Defines new name for the file, passed with given HTML element's name.
	 * Performing Dir::upload() method, such file will be moved to target dir 
	 * and renamed with specified filename.
	 *
	 * @param string new name of file
	 * @param string name of HTML element
	 * @param string optional additional index
	 * @return UploadedFiles this object
	 */
    function setFileName($newFileName, $w_name, $additional_id = null)
    {
        $name = isset($additional_id)?$w_name."\\".$additional_id:$w_name;
        if (isset($this->http_files[$name])) $this->http_files[$name]['name'] = $newFileName;

		return $this;
	}
	//}}}

	//{{{ getUploaded
	/**
	 * Returns raw info for all successfully uploaded files and 
	 * with files, which has passed all specified filters.
	 *
	 * @param null
	 * @return array of arrays with info
	 * @see getFileRaw
	 */
    function getUploaded(){
        $uploaded = array();
        foreach($this->http_files as $name => $info)
            if (!isset($this->http_error_files[$name])) $uploaded[] = $info;
        return $uploaded;
	}
	//}}}

	// {{{ isUploaded
	/**
	 * It checks whenever user tried to upload file or just
	 * left this HTML element blank.
	 * 
	 * It's suitable for optional WFile fields.
	 * <pre><code>
	 * function imageChecker($post){
	 *		$uf = new UploadedFiles('image');
	 *		if (!$uf->isUploaded('image')) return; // no image was selected
	 *
	 *      $uf->allowedMimesLike('image/')->allowedSize(0, 4000*1024)->count(1);
	 *      if ($uf->hasErrors()) throw new CheckerException ($uf->getErrorsFor('image'), 'image');
	 * }
	 * </code></pre>
	 *
	 * It also used by automatic checkers in POSTChecker class.
	 *
	 * @param string name of HTML element
	 * @param string optional additional index
	 * @param bool 
	 */
	function isUploaded($w_name, $additional_id=null){
		$name = isset($additional_id)?$w_name."\\".$additional_id:$w_name;
		return  ( isset($this->http_files[$name]) &&
			(!isset( $this->http_error_files[$name][0][0]) ||  $this->http_error_files[$name][0][0] != UPLOAD_ERR_NO_FILE ));
	}
	//}}}

	//{{{ hasErrors
	/**
	 * Detects, if error occurred while uploading files.
	 *
	 * @param null
	 * @return bool
	 */
    function hasErrors()
    {
        return !empty($this->http_error_files);
	}
	//}}}

	//{{{ getErrorsFor
	/**
	 * Returns textual string of occurred error.
	 *
	 * @param string name of HTML element
	 * @param string optional additional index
	 * @return string error string
	 */
    function getErrorsFor($w_name, $additional_id = null)
    {
		$name = isset($additional_id)?$w_name."\\".$additional_id:$w_name;
		if (!isset($this->http_error_files[$name])) return null;
		$err = array_map(create_function('$a', 'return  call_user_func_array("Language::message", $a);'), 
			array_map(create_function('$e', 'array_unshift($e,"upload");return $e;'), $this->http_error_files[$name]));
		return implode("<br/>", $err);
	}
	//}}}

	//{{{ maxCount
	/**
	 * Reduces count of files in the list to given value.
	 * Caution, action performs at the calling time.
	 *
	 * @param int max number of files
	 * @return UploadedFiles object
	 * @see count
	 */
    function maxCount($max_count)
	{
		$max_count = abs($max_count);
        if(count($this->http_files) > $max_count) 
            $this->http_files = array_slice($this->http_files,0,$max_count);
        return $this;
	}
	//}}}

	//{{{ count
	/**
	 * Strictly defines count of elements. If current length of the list
	 * is not equal to given value, all list will be flushed. So it's length will be
	 * equal 0.
	 * Caution, action performs at the calling time.
	 *
	 * @param int strict number of files
	 * @return UploadedFiles object
	 * @see maxCount
	 */
    function count($count)
    {
        if(count($this->http_files) != $count)
            $this->http_files = array();
        return $this;
	}
	//}}}

	//{{{ excludeExtensions
	/**
	 * Excludes files with passed extension or array of extensions from the list of uploaded files.
	 * Caution, action performs at the calling time.
	 *
	 * @param mixed could be either string or array of disallowed extensions. 
	 * @return UploadedFiles object
	 * @see allowedExtensions
	 */
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
	//}}}

	//{{{ allowedExtensions
	/**
	 * Defines white-list of allowed extensions for uploaded files.
	 * Caution, action performs at the calling time.
	 *
	 * @param mixed could be either string or array of allowed extensions. 
	 * @return UploadedFiles object
	 * @see allowedExtensions
	 */
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
	//}}}

	//{{{ excludeMimes
	/**
	 * Excludes files with passed mime-type or array of mime-types from the list of uploaded files.
	 * Caution, action performs at the calling time.
	 *
	 * @param mixed could be either string or array of disallowed mimes. 
	 * @return UploadedFiles object
	 * @see allowedMimesLike
	 * @see allowedMimes
	 */
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
	//}}}

	//{{{ allowedMimes
	/**
	 * Pass only files with given mime-type or array of mime-types from the list of uploaded files.
	 * Caution, action performs at the calling time.
	 *
	 * @param mixed could be either string or array of allowed mimes. 
	 * @return UploadedFiles object
	 * @see excludeMimes
	 * @see allowedMimesLike
	 */
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
	//}}}

	//{{{ excludeMimesLike
	/**
	 * Filter all files which has partial matching with 
	 * given mime-type or array of mime-types.
	 * For example: <code>$uploaded_files->excludeMimesLike("application/vnd.ms-")</code>
	 * Caution, action performs at the calling time.
	 *
	 * @param mixed could be either string or array of allowed mimes. 
	 * @return UploadedFiles object
	 * @see excludeMimes
	 * @see allowedMimesLike
	 */
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
	//}}}
	
	//{{{ allowedMimesLike
	/**
	 * Pass only files which has partial matching with 
	 * given mime-type or array of mime-types.
	 * For example: <code>$uploaded_files->excludeMimesLike("image/")</code>
	 * Caution, action performs at the calling time.
	 *
	 * @param mixed could be either string or array of allowed mimes. 
	 * @return UploadedFiles object
	 * @see excludeMimes
	 * @see excludeMimesLike
	 */
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
	//}}}

	//{{{ allowedSize
	/**
	 * Pass only files with filesize between max and min values.
	 * Caution, action performs at the calling time.
	 *
	 * @param int max file size
	 * @param int min file size
	 * @return UploadedFiles object
	 * @see allowedImageSize
	 */
    function allowedSize($max = null, $min = 0)
    {
        if($min === null || $max === null) return $this;
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
	//}}}

	//{{{ allowedImageSize
	/**
	 * Pass only images with dimension size between given values.
	 * Caution, action performs at the calling time.
	 *
	 * @param int max image width
	 * @param int max image height
	 * @param int min image width
	 * @param int min image height
	 * @return UploadedFiles object
	 * @see allowedSize
	 */
    function allowedImageSize($max_width = 800, $max_height=600, $min_width = 0,$min_height = 0)
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
	//}}}

	//{{{ onlyCompressed
	/**
	 * Pass only compressed files to the uploaded file list.
	 * Compressed files detects by the <code>self::$compressed_mimes</code> constant.
	 * Caution, action performs at the calling time.
	 *
	 * @param null
	 * @return UploadedFiles object
	 * @see onlyImages
	 */
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
	//}}}

	//{{{ onlyImages
	/**
	 * Pass only images to the uploaded file list.
	 * Caution, action performs at the calling time.
	 *
	 * @param null
	 * @return UploadedFiles object
	 * @see onlyCompressed
	 */
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
	//}}}
}
//}}}
