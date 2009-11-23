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

require_once 'MailTransport.php';

class SmtpException extends Exception{}

/*{{{class smtpMail
 *класс содержит методы для отправки писем с помощью smtp протокола
 */
class SmtpMail extends MailTransport{
    protected  $smtpHost        = null;
	protected  $smtpPort        = null;
	protected  $smtpProto       = null;
	protected  $smtpUser        = null;
	protected  $smtpPassw       = null;
    protected  $smtpTimeo       = 30;
    private $smtpCt             = "";

    function __construct()
    {
        $this->smtpHost = Config::getInstance()->mail->smtp_host;
        $this->smtpPort = Config::getInstance()->mail->smtp_port;
        $this->smtpProto =Config::getInstance()->mail->smtp_proto;
        $this->smtpUser = Config::getInstance()->mail->smtp_user;
        $this->smtpPassw =Config::getInstance()->mail->smtp_passwd;
    }
    /*{{{smtpConnect
     *устанавливает соединение с smtp сервером
     */
    private function smtpConnect(){
        if($this->smtpProto == "ssl")
            $this->smtpCt = fsockopen("ssl://".$this->smtpHost,$this->smtpPort,$errno,$errstr,$this->smtpTimeo);
        else $this->smtpCt = fsockopen($this->smtpHost,$this->smtpPort,$errno,$errstr,$this->smtpTimeo);
        sleep(1);
		if(!is_resource($this->smtpCt))
			{
				throw new SmtpException("<b>Cannot connect with smtp server!!!!</b> ");
			}
    }
    /*}}}*/

    /*{{{getData
     *необходима для считывания ответа от сервера. Возвращает первые 3 символа ответа(код) 
     */
    private function getData($conn){
        $data="";
		while($str = fgets($conn,515)){
			$data .= $str;
			if(substr($str,3,1) == " ") { break; }
		}
  		return $data;
    }
    /*}}}*/
    
    /*{{{tls
     *функция устанавливает tls соединение с сервером
     *используется после установки соединения с сервером и после команды EHLO [host]    
     */
	private function tls() {

    if(!$this->smtpCt) {
       return false;
    }

    fputs($this->smtpCt,"STARTTLS \r\n");
    $code = substr($this->getData($this->smtpCt),0,3);

    if($code != 220) {
      $this->errMsg('tls','');fclose($this->smtpCt);
      }
    
	if(!stream_socket_enable_crypto($this->smtpCt, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
		return false;
	}

    return true;
    }
    /*}}}*/
    
    /*{{{send
     *отправка сообщения через установленное соединение
     *в качестве входного параметра указывается указатель на обьект    
     */
    public function send($pointer){
        try{
            $this->smtpConnect();
        	$data = $this->getData($this->smtpCt);
	        fputs($this->smtpCt,"EHLO ".$this->smtpHost."\r\n");
	        $code = substr($this->getData($this->smtpCt),0,3);
            if($code!= 250) {$this->errMsg('ehlo',' ');fclose($this->smtpCt);return false;}

           
            if(Config::getInstance()->mail->use_smtp_tls && $this->smtpProto != "ssl" && extension_loaded('openssl'))
            {
                $this->tls();
                fputs($this->smtpCt,"EHLO ".$this->smtpHost."\r\n");
                $code = substr($this->getData($this->smtpCt),0,3);
                if($code!= 250) {$this->errMsg('ehlo',' ');fclose($this->smtpCt);return false;}
            }

            if(Config::getInstance()->mail->use_smtp_auth)
            {
                fputs($this->smtpCt,"AUTH LOGIN\r\n");
                $code = substr($this->getData($this->smtpCt),0,3);
                if($code != 334) {$this->errMsg('authLogin',' ');fclose($this->smtpCt);return false;}
                
                fputs($this->smtpCt,base64_encode($this->smtpUser)."\r\n");
                $code = substr($this->getData($this->smtpCt),0,3);
                if($code != 334) {$this->errMsg('access',$this->smtpUser);fclose($this->smtpCt);return false;}
                
                fputs($this->smtpCt,base64_encode($this->smtpPassw)."\r\n");
                $code = substr($this->getData($this->smtpCt),0,3);
                if($code != 235) {$this->errMsg('password',$this->smtpPassw);fclose($this->smtpCt);return false;}
            }
                        
			fputs($this->smtpCt,"MAIL FROM:".'<'.$pointer->getFrom().'>'."\r\n");
			$code = substr($this->getData($this->smtpCt),0,3);
            
			if($code != 250) {$this->errMsg('mailFrom',' ');fclose($this->smtpCt);return false;}
            
            if ((count($pointer->getTo()))>=1){
                foreach($pointer->getTo() as $t)
					{fputs($this->smtpCt,"RCPT TO: ".'<'.$t.'>'."\r\n");
					$code = substr($this->getData($this->smtpCt),0,3);
					if($code != 250 AND $code != 251) {$this->errMsg('rcpt',' ');fclose($this->smtpCt);return false;}
					}
			}

			if ((count($pointer->getCc()))>=1){
				foreach($pointer->getCc() as $c)
					{fputs($this->smtpCt,"RCPT TO: ".'<'.$c.'>'."\r\n");
					$code = substr($this->getData($this->smtpCt),0,3);
					if($code != 250 AND $code != 251) {$this->errMsg('rcpt','->cc');fclose($this->smtpCt);return false;}
					}
			}
			if (count($pointer->getBcc())>=1){
				foreach($pointer->getBcc() as $b)
					{fputs($this->smtpCt,"RCPT TO: ".'<'.$b.'>'."\r\n");
					$code = substr($this->getData($this->smtpCt),0,3);
					if($code != 250 AND $code != 251) {$this->errMsg('rcpt','->bcc');fclose($this->smtpCt);return false;}
					}
			}

			fputs($this->smtpCt,"DATA \r\n");
			$code = substr($this->getData($this->smtpCt),0,3);
            if($code != 354) {$this->errMsg('data',' ');fclose($this->smtpCt);return false;}
            if($pointer->memoryLimit)
            {
                fputs($this->smtpCt,$pointer->createHeader()."\r\n");
                $pointer->LargeBody($this->smtpCt,'fputs');
                fputs($this->smtpCt,"\r\n.\r\n");
            }
            else
                fputs($this->smtpCt,$pointer->createHeader()."\r\n".$pointer->mailBody()."\r\n.\r\n");

			$code = substr($this->getData($this->smtpCt),0,3);
			if($code != 250) {$this->errMsg('sendMessage',' ');fclose($this->smtpCt);return false;}
			fputs($this->smtpCt,"QUIT"."\r\n");
			fclose($this->smtpCt);
		} catch (SmtpException $e){echo $e->getMessage();return false;}
            return true;
    }
    /*}}}*/
    
    /*{{{errMsg
     *вывод на экран возможной ошибки передачи сообщения
     */
	private function errMsg($err,$add){
    	$error=array(
    		'smtp'			=>'Error: smtp error ',
    		'ehlo'			=>'Error of  EHLO',
    		'mailFrom'		=>'Server eror of MAIL FROM',
    		'rcpt'			=>'Server eror of RCPT TO',
    		'data'			=>'Server eror of DATA',
    		'sendMessage'	=>'Error of sending message',
    		'authLogin'		=>'Server eror of AUTH LOGIN',
    		'access'		=>'Error of access with this name',
            'password'		=>'Uncorrect password',
            'tls'           =>'Error of STARTTLS'
        );
        // UNCOMMENT FOR DEBUG
    	//echo $error[$err]." ".$add;
    }
    /*}}}*/

}
/*}}}*/
?>
