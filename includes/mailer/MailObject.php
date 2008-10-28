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

require_once 'IMailObject.php';

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
 */

class MailObject implements IMailObject{
	
    /*{{{class variables
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
	private $htmlMsg        ="";
    private $subject        ='';
    /*
     *$bound,$boundary,$boundaryEnd устанавливают разделитель,который будет разделять части письма типа multipart/mixed
     *    
     *@var string
     *@access private
     */
	private $bound			='sender0405ABB00B';
	private $boundary       ='--sender0405ABB00B';
	private $boundaryEnd    ='--';
    private $contentType    ='text/plain';
    /*
     *$xMailer содержит название мейлера, с помощью которого будет отослано письмо
     *Оно будет добавлено в заголовок письма
     *    
     *@var string
     *@access private
     */
	private $xMailer        ='X-Mailer: The Sender(v0.0.0.1)Testing';
	private $rn             ="\r\n";
	private $msgFlag		='';
    private $er				="false";
    /*
     *Массив адресов получателей
     *    
     *@var array
     *@access private     */
    private $to             =array();
    /*
     *Массив получателей отмеченных как "carbon copy" 
     *    
     *@var array
     *@access private
     */
    private $cc             =array();
    /*
     *Массив получателей, отмеченных как "blind carbon copy" 
     *    
     *@var array
     *@access private
     */
    private $bcc            =array();
    /*
     *Список адресов,на которые можно отсылать ответ на текущеее письмо 
     *    
     *@var array
     *@access private
     */
    private $replyTo        =array();
    /*
     *Массивы,которые хранят информмацию о приклеплённых к письму файлах, а так же их содержимое в кодировке base64
     *    
     *@var array
     *@access private
     */
	private $attachmentName =array();
	private $attachmentType =array();
	private $attachmentCod  =array();
    private $attachDispos   =array();
    /*
     *Хранит тип транспорта, с помощью которого будет отправлено письмо
     *    
     *@var string
     *@access private
     */
	private $transport		='';
    /*}}}*/

    /*{{{__construct
     *сохраняет тип используемого транспорта
     */
    public function __construct(MailTransport $transport)
    {
        $this->setFrom(Config::get('MAIL_DEFAULT_FROM'));
        $this->setFromname(Config::get("MAIL_DEFAULT_FROM_NAME"));
        $this->transport = $transport;
    }
    /*}}}*/
    
    /*{{{send
     *отсылает сформированное сообщение с помощью выбранного транспорта
     */
    public function send(){$this->transport->send($this);}
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
        if(!isset($this->htmlMsg))
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
    
    /*{{{replToAdd
     *добавляет в массив адрес на который можно ответить
     */
    public function replToAdd($address){
    	if($this->validMail($address))
    		{$this->replyTo[]=$address;}
    		else $this->errMsg('invalidMail',$address);
    }
    /*}}}*/
    
    /*{{{attachAdd
     *формирует данные, необходимые для приложения файла к письму(имя файла,тип, код) 
     */
    public function attachAdd($path){
        if (!is_file($path))
    		{$this->errMsg('invalidFile',$path);
    		 $this->er="true";
    	     return false;
    		}
	        $filename = basename($path);
			$fop = fopen($path, "r");
			$codeFile = chunk_split(base64_encode(fread($fop, filesize($path))));
			fclose($fop);
			$this->attachmentName[] =$filename;
			$this->attachmentType[] =$this->fileTypes($path);
			$this->attachmentCod[]  =$codeFile;
			$this->attachDispos[]   ="attachment";

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
    	if (!is_file($path)){
    		$this->errMsg('invalidFile',$path);
    		return false;
    	}
		$filename = basename($path);
		$fop = fopen($path, "r");
		$codeFile = chunk_split(base64_encode(fread($fop, filesize($path))));
		fclose($fop);
		$this->attachmentName[] =$filename;
		$this->attachmentType[] =$this->fileTypes($path);
		$this->attachmentCod[]  =$codeFile;
		$this->attachDispos[]   ="inline";
    }
    /*}}}*/
    
    /*{{{createHeader
     *формирует все необходимые заголовки письма(тема, отправитель,получатели,cc,bcc,Contente-Type)
     */
    public function createHeader(){	
        //$headers="Date:".date("D, j M Y G:i:s").$this->rn;
        $headers = "";
    	$headers.="From: =?UTF-8?B?".base64_encode($this->fromName)."?=<".$this->from.">".$this->rn;
        //$headers.=$this->xMailer.$this->rn;
        if (count($this->replyTo)>0){
	        $reply=implode(",",$this->replyTo);
	        $headers.=$reply;
	    }
        $headers.="X-Priority: 3 (Normal)".$this->rn;
        //$headers.="Message-ID: <0405.".date("YmjHis")."Sender>".$this->rn;
        $headers.="To:".implode(",",$this->to).$this->rn;
        if((count($this->cc))>0){
            $headers.="cc:".implode(",",$this->cc).$this->rn; }
        if((count($this->bcc))>0){
  	  		$headers.="bcc:".implode(",",$this->bcc).$this->rn;
        }
        if(!empty($this->subject)){
            $headers.="Subject: =?UTF-8?B?".base64_encode($this->subject)."?=".$this->rn;
        }
        $headers.="MIME-Version: 1.0".$this->rn;

        if ((count($this->attachmentName)>0)&& $this->contentType=='plain'){
	        $headers.="Content-Type: multipart/mixed; boundary=\"".$this->bound."\";".$this->rn;
            $this->msgFlag="1";
        }
        if (count($this->attachmentName)>0 && $this->contentType=='html'){
	        $headers.="Content-Type: multipart/mixed; boundary=\"".$this->bound."\";".$this->rn;
	        $this->msgFlag="2";
        }
        if (count($this->attachmentName)>0 && $this->contentType=='alternative'){
	        $headers.="Content-Type: multipart/mixed; boundary=\"".$this->bound."\";".$this->rn;
	        $this->msgFlag="3";
        }
        if (count($this->attachmentName)==0 && ($this->contentType=='plain')){
	        $headers.="Content-Type: text/plain; charset= UTF-8".$this->rn."Content-Transfer-Encoding: base64".$this->rn;
	        $this->msgFlag="4";
        }
        if (count($this->attachmentName)==0 && $this->contentType=='html'){
	        $headers.="Content-Type: text/html; charset= UTF-8".$this->rn."Content-Transfer-Encoding: base64".$this->rn;
	        $this->msgFlag="5";
        }

        if (count($this->attachmentName)==0 && $this->contentType=='alternative'){
	        $headers.="Content-Type: multipart/mixed; boundary=\"".$this->bound."\";".$this->rn;
	        $this->msgFlag="6";
        }
        return $headers;
    }
    /*}}}*/

    /*{{{mailBody
     *формирует само тело письма, учитывая его Contente-Type и приложенные файлы(inline,attach)
     */
    public function mailBody(){
        $attach='';
 		$inline='';
        switch($this->msgFlag){
            case '1':
   				for($i=0;$i<count($this->attachmentName);$i++){
       			$attach.=$this->boundary.$this->rn."Content-Type:".$this->attachmentType[$i]."; name=\"".$this->attachmentName[$i]."\"".$this->rn."Content-Transfer-Encoding: base64 \r\n"."Content-Disposition: ".$this->attachDispos[$i]."; filename=\"".$this->attachmentName[$i]."\" \r\n".$this->rn.$this->attachmentCod[$i];
       			}
       			$attach.=$this->boundary.$this->boundaryEnd;
            	$body=$this->boundary.$this->rn."Content-Type:text/plain;charset= UTF-8".$this->rn."Content-Transfer-Encoding: base64 \r\n".$this->rn.chunk_split(base64_encode($this->msg)).$this->rn.$attach;
   			break;
   			case '2':
   				for($i=0;$i<count($this->attachmentName);$i++){
       			$attach.=$this->boundary.$this->rn."Content-Type:".$this->attachmentType[$i]."; name=\"".$this->attachmentName[$i]."\"".$this->rn."Content-Transfer-Encoding: base64".$this->rn."Content-Disposition: ".$this->attachDispos[$i]."; filename=\"".$this->attachmentName[$i]."\" \r\n".$this->rn.$this->attachmentCod[$i];
       			}
       			$attach.=$this->boundary.$this->boundaryEnd;
            	$body=$this->boundary.$this->rn."Content-Type:text/html;charset= UTF-8".$this->rn."Content-Transfer-Encoding: base64 \r\n".$this->rn.chunk_split(base64_encode($this->htmlMsg)).$this->rn.$attach;
   			break;
   			case '3':
   				for($i=0;$i<count($this->attachmentName);$i++){
       			$attach.=$this->boundary.$this->rn."Content-Type:".$this->attachmentType[$i]."; name=\"".$this->attachmentName[$i]."\"".$this->rn."Content-Transfer-Encoding: base64".$this->rn."Content-Disposition: ".$this->attachDispos[$i]."; filename=\"".$this->attachmentName[$i]."\" \r\n\r\n".$this->attachmentCod[$i];
       			}
       			$attach.=$this->boundary.$this->boundaryEnd;
            	$body=$this->boundary.$this->rn."Content-Type:text/plain;charset= UTF-8".$this->rn."Content-Transfer-Encoding: base64 \r\n".$this->rn.chunk_split(base64_encode($this->msg)).$this->rn.$this->boundary.$this->rn."Content-Type:text/Html;charset= UTF-8".$this->rn."Content-Transfer-Encoding: base64 \r\n".$this->rn.base64_encode($this->htmlMsg).$this->rn.$attach;
   			break;
   			case '4':
   				$body=chunk_split(base64_encode($this->msg)).$this->rn;
   			break;
   			case '5':
   				$body=chunk_split(base64_encode($this->htmlMsg)).$this->rn;
   			break;
   			case '6':
   				$body=$this->boundary.$this->rn."Content-Type:text/plain;charset= UTF-8".$this->rn."Content-Transfer-Encoding: base64 \r\n".$this->rn.chunk_split(base64_encode($this->msg)).$this->rn.$this->boundary.$this->rn."Content-Type:text/Html;charset= UTF-8".$this->rn."Content-Transfer-Encoding: base64 \r\n".$this->rn.base64_encode($this->htmlMsg).$this->rn."\r\n".$this->boundary.$this->boundaryEnd;
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
		return true;//preg_match($reg, $address);!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    }
    /*}}}*/
    
    /*{{{errMsg
     *выводит на экран сообщение об ошибке,которая может возникнуть при формировании заголовков и тела письма 
     */
    private function errMsg($err,$add){
    	$error=array(
    		'invalidMail'	=>'Error: illegal E-mail address:',
    		'invalidFile'	=>'Error: illegal file name:',
    		//'smtp'			=>'Error: smtp error ',
    		'rcptAddress'	=>'Enter recipient E-mail address!'
        );
        // FOR DEBUG - UNCOMMENT
        //echo"<strong>".$error[$err]."</strong> ".$add."<br />";
    }
    /*}}}*/
    
    /*{{{fileTypes
     *функция, использующая расширение php fileinfo- выводит Mime-type приложенного к письму файла
     */
    private function fileTypes($path) {
        $finfo = finfo_open( FILEINFO_MIME);
		$mimeType = finfo_file($finfo,realpath($path));
		finfo_close( $finfo);
	   	return $mimeType;
    }
    /*}}}*/

}
/*}}}*/
?>
