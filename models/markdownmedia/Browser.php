<?php

class Browser
{

	static function ls($path)
	{
		$ipath = implode("/",$path);
		$r = new ResultSet();
		$ls = t(new FileStorage("/images"))->ls($ipath);
		$r->f("#dirs")->count(count(@$ls['dirs']))
			->f("#parent > whyperlink")->href(array_merge(array_slice($path,0,-1),array(null)))
			;

		if(!empty($ls['dirs']))
			foreach($ls['dirs'] as $k => $d)
			{
				$r->f("#cd > whyperlink",$k)->href(array_merge($path,array($d)))
					->f("#ddel",$k)->additional_id($d)
					->f("#cd wtext",$k)->text($d);
			}
		$fc = 0;
		if(!empty($ls['files']))
			foreach($ls['files'] as $k => $f)
			{
				$df = new MimeDecorator(new StatDecorator($f));
				if(strpos($df->getMime(),"image") === false) continue;
				$df = new ImageDecorator($df);
				$stat = $df->stat();
				$r->f("#fname",$k)->text($df->getName())
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

	static function mkdir($post,$path)
	{
		if(empty($post->dirname)) return;
		$ipath = implode("/",$path);
		t(new FileStorage("/images/".$ipath))->mkdir($post->dirname);
	}

	static function delete($post,$path)
	{
		$f = null;
		if(!empty($post->fdel)) 
			$f = key($post->fdel);
		elseif(!empty($post->ddel))
			$f = key($post->ddel);
		else return;

		$ipath = implode("/",$path);
		t(new FileStorage("/images/".$ipath))->rm($f);
	}
	static function upload($post,$path)
	{
		$uf = t(new UploadedFiles("uploaded_file"))
			->count(1)->onlyImages();
		if(!empty($post->upload_rename))
			$uf->setFileName($post->upload_rename, "uploaded_file");
		$ipath = implode("/",$path);
        t(new FileStorage("/images/"))->upload($uf, $ipath);
	}
}

?>
