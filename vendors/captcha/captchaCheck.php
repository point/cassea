<?php
class CaptchaCheck{
    static public function check($post,$signature)
    {
        $sId =  Storage::createWithSession("controller".Controller::getInstance()->getStoragePostfix());
        if($cName=$sId->get($signature))
           if($sId->get('signature') == $signature)
            { 
                $sId->un_set($signature);
                $sId->un_set('signature');
                $sCheck=Storage::createWithSession("_CAPTCHA_",60);
                if(!((strtoupper($post->{$cName}) === $sCheck->get("answer"))&&
                    (Controller::getInstance()->getPage() === $sCheck->get("page"))))
                        POSTErrors::addError("__".$cName,null,Language::message('widgets',"captcha_error"));
            }
    }

    static public function setCAPTCHA($captchaName = null,$signature=null)
    {
        if(empty($captchaName) || empty($signature)) return;
        $storage =  Storage::createWithSession("controller".Controller::getInstance()->getStoragePostfix());
        $storage -> set($signature,$captchaName);
        $storage -> set('signature',$signature);
    }
}
?>
