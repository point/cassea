<?php
class imagickCaptcha{
    
    public static function generateCaptchaImg(&$str,$fontColor,$bgColor)
    {
        $image=new IMagick();
        $draw=new ImagickDraw();
        $image->newImage(155,50,new ImagickPixel($bgColor));
        $draw->setFontSize(42);
        $draw->setFont(Config::get('root_dir').'/vendors/captcha/c.ttf');
        $pixel=new ImagickPixel($fontColor);
        $draw->setFillColor($pixel);
            for($j=1;$j<4;$j++)
            {
                for($i=0;$i<100;$i++)
                {
                    $a[$i]['x']=$i*357;
                    $a[$i]['y']=round(2*sin($i+mt_rand(1,7))+$j*12);
                }
                $draw->polyline($a);
                $image->drawImage($draw);
             }   
         $str=randStr(Config::getInstance()->captcha->word_length); 

         $image->annotateImage($draw,5,40,0,$str);
         $image->waveImage(mt_rand(3,5),mt_rand(30,60));
         $image->vignetteImage(5,150,0,0);
         $image->swirlImage(mt_rand(10,39));
         $image->setImageFormat('png');
         $image->paintTransparentImage('rgb(255,255,255)', 0.0, 0.0);;
         return $image;
    }
}
?>
