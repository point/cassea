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

/*{{{class mailObject
 *Класс "mailObject",интерфейсом которого является "ImailObject"
 *предназначен для формирования самого тела письма(в кодировке UTF-8) которое впоследствии приймет транспорт.
 *Реализованы следующие возможности:
 *    Формирование письма для доставки нескольким адресатам (CC, Bcc).
 *    Формирование HTML письма.
 *    Формирование Multipart/alternative письма.
 *    Добавление аттачментов.
 *    Добавление inline картинок.
 *    Валидация E-mail адресов.
 *    ReplyTo
 */

class MailObject implements iMailObject{
	
    //{{{class variables
    
    const cteb64 =    "Content-Transfer-Encoding: base64";
	const rn     = "\r\n";

    /**
     *содержит адрес отправителя
     *    
     *@var string
     *@access private
     */
    private $from           = null;
    /*
     *имя отправителя, в результате получится Имя<адрес> 
     *    
     *@var string
     *@access private
     */
    private $fromName       = null;
    /*
     *текст письма тип которого text/plain
     *    
     *@var string
     *@access private
     */
    private $msg            = null;
    /*
     *Текст Html письма (text/html)
     *    
     *@var string
     *@access private
     */
	private $htmlMsg        = null;
    private $subject        = '';
    /*
     *$bound,$boundary,$boundaryEnd устанавливают разделитель,который будет разделять части письма типа multipart/mixed
     *    
     *@var string
     *@access private
     */
	private $bound			= 'sender0405ABB00B';
	private $boundary       = '--sender0405ABB00B';
	private $boundaryEnd    = '--';
    private $contentType    = 'text/plain';
    /*
     *$xMailer содержит название мейлера, с помощью которого будет отослано письмо
     *Оно будет добавлено в заголовок письма
     *    
     *@var string
     *@access private
     */
	private $xMailer        = 'X-Mailer: The Sender(v0.0.0.1)Testing';
	private $msgFlag		= '';
    private $er				= "false";
    /*
     *Массив адресов получателей
     *    
     *@var array
     *@access private     */
    private $to             = array();
    /*
     *Массив получателей отмеченных как "carbon copy" 
     *    
     *@var array
     *@access private
     */
    private $cc             = array();
    /*
     *Массив получателей, отмеченных как "blind carbon copy" 
     *    
     *@var array
     *@access private
     */
    private $bcc            = array();
    /*
     *Список адресов,на которые можно отсылать ответ на текущеее письмо 
     *    
     *@var array
     *@access private
     */
    private $replyTo        = array();
    /*
     *Массивы,которые хранят информмацию о приклеплённых к письму файлах, а так же их содержимое 
     *в кодировке base64
     *    
     *@var array
     *@access private
     */
	private $attachmentName = array();
	private $attachmentType = array();
	private $attachmentPath  = array();
    private $attachDispos   = array();
    private $attachSize     = null;
    /*
     *Хранит тип транспорта, с помощью которого будет отправлено письмо
     *    
     *@var string
     *@access private
     */
    private $transport		= '';

    /**
     * Флаг для перехода в режим работы с файлами большого размера. 
     * Устанавливаеться в true если суммарный размер прикреплённых файлов больше чем memory_limit
     * 
     */
    public $memoryLimit    = false;
        
    /*}}}*/

    /*{{{__construct
     *сохраняет тип используемого транспорта
     */
    public function __construct(MailTransport $transport)
    {
        $this->setFrom(Config::getInstance()->mail->default_from);
        $this->setFromname(Config::getInstance()->mail->default_from_name);
        $this->transport = $transport;
    }
    /*}}}*/
    
    /*{{{send
     *отсылает сформированное сообщение с помощью выбранного транспорта
     */
    public function send()
    {
        return $this->transport->send($this);
    }
    /*}}}*/

    /*{{{setText
     *устанавливает contenteType в plain
     */
    public function setText(){$this->contentType='plain';}
    /*}}}*/

    /*{{{setHtml
     *устанавливает html
     */
    public function setHtml(){$this->contentType='html';}
    /*}}}*/

    /*{{{setTexthtml
     *используется когда необходимо отослать и plain  и html тексты 
     */
    public function setTextHtml(){$this->contentType='alternative';}
    /*}}}*/

    /*{{{setSubject
     *определяет тему формируемого письма
     */
    public function setSubject($sub){$this->subject=$sub;}
    /*}}}*/

    /*{{{setFromname
     *определяет имя отправителя
     */
    public function setFromname($name){$this->fromName=$name;}
    /*}}}*/

    /*{{{stFrom
     *устанавливает адрес, с которого будет отправлено письмо
     */
    public function setFrom($address){$this->from=$address;}
    /*}}}*/

    /*{{{message
     *текст письма
     */
    public function message($msg){
        if(is_null($this->htmlMsg))
            $this->setText();
        else $this->setTextHtml();
        $this->msg=$msg;
    }
    /*}}}*/ 

    /*{{{htmlMessage
     *тект html письма
     */
    public function htmlMessage($htmlMsg)
    {
        if(!isset($this->msg))
            $this->setHtml();
        else $this->setTextHtml();
        $this->htmlMsg=$htmlMsg;
    }
    /*}}}*/ 

    /*{{{getTo
     *возвращает массив адресов получателей
     */
    public function getTo(){return $this->to;}
    /*}}}*/

    /*{{{getSubject
     *возвращает тему письма
     */
    public function getSubject(){return $this->subject;}
    /*}}}*/

    /*{{{getFrom
     *возвращает адрес отправителя
     */
    public function getFrom()   {return $this->from;}
    /*}}}*/

    /*{{{getCc
     *
     */
    public function getCc()     {return $this->cc;}
    /*}}}*/

    /*{{{getBcc
     *
     */
    public function getBcc()    {return $this->bcc;}
    /*}}}*/

    /*{{{toAdd
     * добавляет в массив получателей новый адрес
     */
    public function toAdd($address){
        if($this->validMail($address))
    		{$this->to[]=$address;}
    		else $this->errMsg('invalidMail',$address);
    }
    /*}}}*/
    
    /*{{{replyToAdd
     *добавляет в массив адрес на который можно ответить
     */
    public function replyToAdd($address){
    	if($this->validMail($address))
    		{$this->replyTo[]=$address;}
    		else $this->errMsg('invalidMail',$address);
    }
    /*}}}*/
    
    /*{{{attachAdd
     *формирует данные, необходимые для приложения файла к письму(имя файла,тип, код) 
     */
    public function attachAdd($path){
        if (!is_file($path)|| !is_readable($path))
    	{
            $this->errMsg('invalidFile',$path);
    		$this->er="true";
    	    return false;
        }
        $filename = basename($path);
        $this-> attachmentName[] = $filename;
        $this-> attachmentType[] = getMime($path);
        $this-> attachmentPath[]  = $path;
        $this-> attachDispos[]   = "attachment";
        $this-> attachSize      += filesize($path);
        if($this->attachSize >= max(sizeFromString(ini_get("memory_limit")),4*1048576)) $this->memoryLimit = true;
    }
    /*}}}*/
    
    /*{{{ccAdd
     *
     */
    public function ccAdd($address){
        if($this->validMail($address))
    		{$this->cc[]=$address;}
    		else {$this->errMsg('invalidMail',$address);}
    }
    /*}}}*/
    
    /*{{{bccAdd
     *
     */
    public function bccAdd($address){
        if($this->validMail($address))
    		{$this->bcc[]=$address;}
        else {$this->errMsg('invalidMail',$address);;}
    }
    /*}}}*/
    
    /*{{{inlineAddImg
     *
     */
    public function inlineAdd($path){
    	if (!is_file($path)|| !is_readable($path)){
    		$this->errMsg('invalidFile',$path);
    		return false;
    	}
		$filename = basename($path);
		$this-> attachmentName[] =$filename;
		$this-> attachmentType[] =getMime($path);
		$this->attachmentPath[]  =$path;
        $this-> attachSize      += filesize($path);
        $this-> attachDispos[]   ="inline";
        if($this->attachSize >= max(sizeFromString(ini_get("memory_limit")),4*1048576)) $this->memoryLimit = true;
    }
    /*}}}*/
    
    /*{{{createHeader
     *формирует все необходимые заголовки письма(тема, отправитель,получатели,cc,bcc,Contente-Type)
     */
    public function createHeader(){	

        //$headers="Date:".date("D, j M Y G:i:s").self::rn;
        $headers = "";
    	$headers.="From: =?UTF-8?B?".base64_encode($this->fromName)."?=<".$this->from.">".self::rn;
        //$headers.=$this->xMailer.self::rn;
        if (count($this->replyTo)>0){
            $reply=implode(",",$this->replyTo);
	        $headers.="Reply-To: ".$reply.self::rn;
	    }
        $headers.="X-Priority: 3 (Normal)".self::rn;
        //$headers.="Message-ID: <0405.".date("YmjHis")."Sender>".self::rn;
        $headers.="To:".implode(",",$this->to).self::rn;
        if((count($this->cc))>0){
            $headers.="cc:".implode(",",$this->cc).self::rn; }
        if((count($this->bcc))>0){
  	  		$headers.="Bcc:".implode(",",$this->bcc).self::rn;
        }
        if(!empty($this->subject)){
            $headers.="Subject: =?UTF-8?B?".base64_encode($this->subject)."?=".self::rn;
        }
        $headers.="MIME-Version: 1.0".self::rn;

        if ((count($this->attachmentName)>0)&& $this->contentType=='plain'){
	        $headers.="Content-Type: multipart/mixed; boundary=\"".$this->bound."\";".self::rn;
            $this->msgFlag="1";
        }
        if (count($this->attachmentName)>0 && $this->contentType=='html'){
	        $headers.="Content-Type: multipart/mixed; boundary=\"".$this->bound."\";".self::rn;
	        $this->msgFlag="2";
        }
        if (count($this->attachmentName)>0 && $this->contentType=='alternative'){
	        $headers.="Content-Type: multipart/mixed; boundary=\"".$this->bound."\";".self::rn;
	        $this->msgFlag="3";
        }
        if (count($this->attachmentName)==0 && ($this->contentType=='plain')){
	        $headers.="Content-Type: text/plain; charset= UTF-8".self::rn.self::cteb64.self::rn;
	        $this->msgFlag="4";
        }
        if (count($this->attachmentName)==0 && $this->contentType=='html'){
	        $headers.="Content-Type: text/html; charset= UTF-8".self::rn.self::cteb64.self::rn;
	        $this->msgFlag="5";
        }

        if (count($this->attachmentName)==0 && $this->contentType=='alternative'){
	        $headers.="Content-Type: multipart/mixed; boundary=\"".$this->bound."\";".self::rn;
	        $this->msgFlag="6";
        }
        return $headers;
    }
    /*}}}*/
    
    //{{{largeBody
    /**
     *Используеться в случае, если флаг memoryLimit=true, т.е. к сообщению приложенны файлы большого размера.
     *С помощью этого метода тело письма отсылаеться порциями по 4096 байт, добавляя закодированые в base64 фрагменты файла в поток.
     *Формирует само тело письма, учитывая его Contente-Type и приложенные файлы(inline,attach).
     *В качестве параметров использует ресурс соединения к smtp серверу а так же имя функции, с помощью которой происходит запись в поток.
     */
    public function largeBody($connect,$func){
        switch($this->msgFlag){
        case '1':
                $func($connect,$this->boundary.self::rn."Content-Type:text/plain;charset= UTF-8".self::rn.self::cteb64." ".self::rn.
                    self::rn.chunk_split(base64_encode($this->msg)).self::rn);

                for($i=0;$i<count($this->attachmentName);$i++){

                    $func($connect,$this->boundary.self::rn."Content-Type:".$this->attachmentType[$i]."; name=\"".$this->attachmentName[$i]."\"".self::rn.
                        self::cteb64." \r\n"."Content-Disposition: ".$this->attachDispos[$i]."; filename=\"".
                        $this->attachmentName[$i]."\" ".self::rn.self::rn);
                    $file= fopen($this->attachmentPath[$i],'r');
                    $this->base64Encode($file,$connect,$func);
                }
       			$func($connect,$this->boundary.$this->boundaryEnd);
   			break;
            
            case '2':
                $func($connect,$this->boundary.self::rn."Content-Type:text/html;charset= UTF-8".self::rn.self::cteb64." ".self::rn.
                    self::rn.chunk_split(base64_encode($this->htmlMsg)).self::rn);

                for($i=0;$i<count($this->attachmentName);$i++){

                    $func($connect,$this->boundary.self::rn."Content-Type:".$this->attachmentType[$i]."; name=\"".$this->attachmentName[$i]."\"".self::rn.
                        self::cteb64." ".self::rn."Content-Disposition: ".$this->attachDispos[$i]."; filename=\"".
                        $this->attachmentName[$i]."\" ".self::rn.self::rn);
                    
                    $file= fopen($this->attachmentPath[$i],'rb');
                    $this->base64Encode($file,$connect,$func);
                }
                $func($connect,$this->boundary.$this->boundaryEnd);
   			break;
            case '3':
                $func($connect,$this->boundary.self::rn."Content-Type:text/plain;charset= UTF-8".self::rn.self::cteb64." ".self::rn.
                    self::rn.chunk_split(base64_encode($this->msg)).self::rn.$this->boundary.self::rn."Content-Type:text/Html;charset= UTF-8".self::rn.
                    self::cteb64." ".self::rn.self::rn.base64_encode($this->htmlMsg).self::rn);

                for($i=0;$i<count($this->attachmentName);$i++){

                    $func($connect,$this->boundary.self::rn."Content-Type:".$this->attachmentType[$i]."; name=\"".$this->attachmentName[$i]."\"".self::rn.
                        self::cteb64." ".self::rn."Content-Disposition: ".$this->attachDispos[$i]."; filename=\"".
                        $this->attachmentName[$i]."\" ".self::rn.self::rn);
                    $file= fopen($this->attachmentPath[$i],'r');
                    $this->base64Encode($file,$connect,$func);
                }
       			$func($connect,$this->boundary.$this->boundaryEnd);
            break;
        }
    }
    //
    //}}}

    //{{{base64Encode
    /**
     *Метод base64Encode производит последовательное кодирование фрагментов по 4К файла $handler в формат base64 и отправку закодированного фрагмента в поток $connect. 
     *В качестве параметров использует ресурс соединения к smtp серверу, открытый на бинарное чтения файл, а так же имя функции, с помощью которой происходит запись в поток.
     */
    public function base64Encode($handler,$connect,$func){
        

        $cache = ''; 
        $eof = false; 

        while (1) { 

            if (!$eof) { 
                if (!feof($handler)) { 
                    $row = fgets($handler, 4096); 
                } else { 
                    $row = ''; 
                    $eof = true; 
                } 
            } 

            if ($cache !== '') 
                $row = $cache.$row; 
            elseif ($eof) 
                break; 

            $b64 = base64_encode($row); 
            $put = ''; 

            if (strlen($b64) < 76) { 
                if ($eof) { 
                    $put = $b64."\n"; 
                    $cache = ''; 
                } else { 
                    $cache = $row; 
                } 

            } elseif (strlen($b64) > 76) { 
                do { 
                    $put .= substr($b64, 0, 76)."\n"; 
                    $b64 = substr($b64, 76); 
                } while (strlen($b64) > 76); 

                $cache = base64_decode($b64); 

            } else { 
                if (!$eof && $b64{75} == '=') { 
                    $cache = $row; 
                } else { 
                    $put = $b64."\n"; 
                    $cache = ''; 
                } 
            } 

            if ($put !== '') { 
                $func($connect, $put); 
        } 
    } 

    fclose($handler);

}
//}}}

//{{{mailBody
    /**
     *Формирует само тело письма, учитывая его Contente-Type и приложенные файлы(inline,attach)
     */
    public function mailBody(){
        $attach='';
        $inline='';
		$body = '';
        switch($this->msgFlag){
            case '1':
                for($i=0;$i<count($this->attachmentName);$i++){
                    $attach.=$this->boundary.self::rn."Content-Type:".$this->attachmentType[$i]."; name=\"".$this->attachmentName[$i]."\"".self::rn.
                        self::cteb64." ".self::rn."Content-Disposition: ".$this->attachDispos[$i]."; filename=\"".
                        $this->attachmentName[$i]."\" ".self::rn.self::rn.chunk_split(base64_encode(file_get_contents($this->attachmentPath[$i])));
                }
       			$attach.=$this->boundary.$this->boundaryEnd;
                $body=$this->boundary.self::rn."Content-Type:text/plain;charset= UTF-8".self::rn.self::cteb64." ".self::rn.
                    self::rn.chunk_split(base64_encode($this->msg)).self::rn.$attach;
   			break;
            case '2':
                for($i=0;$i<count($this->attachmentName);$i++){
                    $attach.=$this->boundary.self::rn."Content-Type:".$this->attachmentType[$i]."; name=\"".$this->attachmentName[$i]."\"".self::rn.
                        self::cteb64.self::rn."Content-Disposition: ".$this->attachDispos[$i]."; filename=\"".
                        $this->attachmentName[$i]."\" ".self::rn.self::rn.chunk_split(base64_encode(file_get_contents($this->attachmentPath[$i])));
                }

       			$attach.=$this->boundary.$this->boundaryEnd;
                $body=$this->boundary.self::rn."Content-Type:text/html;charset= UTF-8".self::rn.self::cteb64." ".self::rn.
                    self::rn.chunk_split(base64_encode($this->htmlMsg)).self::rn.$attach;
   			break;
   			case '3':
                for($i=0;$i<count($this->attachmentName);$i++){
                    $attach.=$this->boundary.self::rn."Content-Type:".$this->attachmentType[$i]."; name=\"".$this->attachmentName[$i]."\"".self::rn.
                        self::cteb64.self::rn."Content-Disposition: ".$this->attachDispos[$i]."; filename=\"".
                        $this->attachmentName[$i]."\" ".self::rn.self::rn.chunk_split(base64_encode(file_get_contents($this->attachmentPath[$i])));
                }

       			$attach.=$this->boundary.$this->boundaryEnd;
                $body=$this->boundary.self::rn."Content-Type:text/plain;charset= UTF-8".self::rn.self::cteb64." ".self::rn.
                    self::rn.chunk_split(base64_encode($this->msg)).self::rn.$this->boundary.self::rn."Content-Type:text/Html;charset= UTF-8".self::rn.
                    self::cteb64." ".self::rn.self::rn.base64_encode($this->htmlMsg).self::rn.$attach;
   			break;
   			case '4':
   				$body=chunk_split(base64_encode($this->msg)).self::rn;
   			break;
   			case '5':
   				$body=chunk_split(base64_encode($this->htmlMsg)).self::rn;
   			break;
   			case '6':
                $body=$this->boundary.self::rn."Content-Type:text/plain;charset= UTF-8".self::rn.self::cteb64." ".self::rn.
                    self::rn.chunk_split(base64_encode($this->msg)).self::rn.$this->boundary.self::rn."Content-Type:text/Html;charset= UTF-8".self::rn.
                    self::cteb64." ".self::rn.self::rn.base64_encode($this->htmlMsg).self::rn.self::rn.$this->boundary.$this->boundaryEnd;
   			break;
   		}
        return $body;
    }
    /*}}}*/
    
    /*{{{validMail
     *производит валидацию используемых E-mail адресов с помощью регулярного приложения
     *возвращает соответственно true или false
     */
    private function validMail($address){
        $reg = "/[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+(?:[A-Z]{2}|com|org|net|gov|mil|biz|info|mobi|name|aero|jobs|museum|ua|ru)/";
		return preg_match($reg, $address);
    }
    /*}}}*/
    
    /*{{{errMsg
     *выводит на экран сообщение об ошибке,которая может возникнуть при формировании заголовков и тела письма 
     */
    private function errMsg($err,$add){
    	$error=array(
    		'invalidMail'	=>'Error: illegal E-mail address:',
    		'invalidFile'	=>'Error: illegal file name:',
    		'rcptAddress'	=>'Enter recipient E-mail address!'
        );
        // FOR DEBUG - UNCOMMENT
        //echo"<strong>".$error[$err]."</strong> ".$add."<br />";
    }
    /*}}}*/
    
}
//}}}
?>
