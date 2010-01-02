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
class Browser
{
	const DIRNAME_PREG='#^[a-zA-Z0-9-_()]+$#';
	const ROOT = 'images';

	static function ls($path)
	{
		$ipath = implode("/",$path);
		$r = new ResultSet();
		$dImages = new Dir(self::ROOT);
		$dir = $dImages->getDir($ipath);
		$dirs = $dir->ls(null, DIR::LS_DIR);
		$r->f("#dirs")->count(count($dirs))
			->f("#parent > whyperlink")->href(array_merge(array_slice($path,0,-1),array(null)))
			;

		foreach($dirs as $k => $d)
		{
			$l = substr($d,strlen($dImages));
			$r->f("#cd > whyperlink",$k)->href(explode('/',$l))
				->f("#ddel",$k)->additional_id($d->getName())
				->f("#cd wtext",$k)->text($d->getName());
		}
		$fc = 0;
		foreach($dir->ls() as $k => $f)
		{
			$df = new MimeDecorator(new StatDecorator($f));
			//if(strpos($df->getMime(),"image") === false) continue;
			try{
			$df = new ImageDecorator($df);
			}catch(DecoratorException $e)
			{ continue; }
			$stat = $df->stat();
			@$r->f("#fname",$k)->text($df->getName())
				->f("#fdel",$k)->additional_id($df->getName())
				->f("#stat",$k)->text(
					$df->getWidth()."&#215;".$df->getHeight()."&nbsp;".
					date("Y/m/d H:i",$stat['mtime'])."&nbsp;".
					sizeToString($stat['size'])	)					
					->f("#preview",$k)->file($df)
					->f("#choose > whyperlink",$k)->href($df->getURL())
					;
			$fc++;
		}
		$r->f("#files")->count($fc);
		return $r;
	}

	static function mkdirChecker($post, $path){
		$dname = trim($post->dirname);
		if (!preg_match(self::DIRNAME_PREG,$dname))
			throw new CheckerException(Language::message('markdownmedia_browser','incorrect_directory_name'), 'dirname');

		if(Dir::get(self::ROOT)->getDir(implode("/",$path))->getDir($dname)->exists())
			throw new CheckerException(Language::message('markdownmedia_browser', 'directory_already_exists', $dname), 'dirname');
	}

	static function mkdir($post,$path)
	{
		Dir::get(self::ROOT)->getDir(implode("/",$path))->mkdir($post->dirname);
	}

	static function delete($post,$path)
	{
		$f = null;
		$d = Dir::get(self::ROOT)->getDir(implode("/",$path));
		if(!empty($post->fdel)) 
			$o = $d->getFile(key($post->fdel))->delete();
		elseif(!empty($post->ddel))
			$o = $d->getDir(key($post->ddel))->delete();
	}
	static function upload($post,$path)
	{
		$uf = t(new UploadedFiles("uploaded_file"))
			->count(1)->onlyImages();
		if(!empty($post->upload_rename))
			$uf->setFileName($post->upload_rename, "uploaded_file");
		$ipath = implode("/",$path);
		$d = Dir::get(self::ROOT)->getDir(implode("/",$path))->upload($uf);
	}
}

?>
