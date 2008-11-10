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

require_once 'MailTransport.php';

class MailException extends Exception{}

/*{{{class sendmailMail
 *класс реализует транспорт sendmail
 *
 */
class SendmailMail extends MailTransport{
    /*{{{$sendmail
     *содержит путь к программе(sendmail)
     */
    private $sendmail = null;
    /*}}}*/ 

    function __construct()
    {
        $this->sendmail = Config::getInstance()->mail->sendmail_path;
    }
    /*{{{send
     *отправляет письмо с помощью внешней программы
     */
    public function send($pointer) {
        try{
		    $fp = popen($this->sendmail." -t -i", "wb");
			if ( ! is_resource($fp))
				{
					throw new MailException("<b>Cannot open {$this->sendmail}!!!!</b> ");
				}

			fwrite($fp,$pointer->createHeader()."\r\n");
			fwrite($fp,$pointer->mailBody()."\r\n");
			fwrite($fp, "\n");
			pclose($fp);
            $this->result();
            return true;
		}catch (MailException $e){echo $e->getMessage();}
    }
    /*}}}*/
}
/*}}}*/

?>
