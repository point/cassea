<?php

class CmdCaptcha extends Command{
    
    private $fontColor  = false;
	private $bgColor    = false;
      
    public function __construct( $workingDir = '.', $info, $commandsSeq = array())
    {
        parent::__construct( $workingDir, $info, $commandsSeq);
    }

    public function cmdGenerate()
    {
		Autoload::addVendor('captcha');
        $this->fontColor= Config::getInstance()->captcha->font_color;
        $this->bgColor  = Config::getInstance()->captcha->background;

        if($o=ArgsHolder::get()->getOption('font')) {if(!$this->setFontColor($o)) return;}
        if($o=ArgsHolder::get()->getOption('bg'))   {if(!$this->setBgColor($o)) return;}
            try{
                umask(0);
                $str = null;
                $s=Storage::create("__CAPTCHALIST__",2592000);
                if(file_exists($c_dir = Config::get('root_dir').Config::getInstance()->captcha->dir))
                    deltree($c_dir);
                mkdir($c_dir);
                for($d = 1;$d < Config::getInstance()->captcha->dirs_count + 1;$d++)
                {
                    mkdir($c_dir."/".$d);
                    for($f = 1;$f < Config::getInstance()->captcha->files_count + 1;$f++)
                    {
                        imagickCaptcha::generateCaptchaImg($str,$this->fontColor,$this->bgColor)->writeImage ($c_dir."/".$d.'/'.$str.'.png');
                        $filenames[$d][$f] = $str;
                        IO::out("Create file $str.png", IO::MESSAGE_INFO);
                    }
                }
                $s->set("files",$filenames);
        }catch(Exception $e){
          throw new ConsoleException($e);
        }
    }
    
    public function setFontColor($color){
        if(preg_match('/^#([0-9a-f]{1,2}){3}$/i', $color)) 
            return $this->fontColor=$color;
        else IO::out("Wrong color format $color It must be #ffffff or #fff.");
    }
    
    public function setBgColor($color){
        if(preg_match('/^#([0-9a-f]{1,2}){3}$/i', $color)) 
            return $this->bgColor=$color;
        else IO::out("Wrong color format $color It must be #ffffff or #fff.");
    }
}
