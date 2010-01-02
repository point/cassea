<?php

class Help
{
	public function dsGetSrc()
    {
        $html = Dir::get('html/markdown');
        $src = 'help.'.Language::currentName().'.html';
        if (!$html->getFIle($src)->exists())
            $src = 'help.en.html';
        $r = new ResultSet();
        return t(new ResultSet())->f('#help')->text(file_get_contents($html->getFile($src)));
	}
}
