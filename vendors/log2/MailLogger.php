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
/**
 *
 * This file contains class WriterMail which abstract from WriterAbstract.
 * WriterMail's responsibility is to send log data by E-mail.
 *
 * @author Skai <climbonn@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 * @see Mail
 */

//{{{ MailLogger
class MailLogger extends AbstractFormattable
/**
 * WriterMail's responsibility is to send log data by E-mail.
 */
{
    
    /**
     * @var E-mail for sending.
     */
    protected $email;
    
    /**
     * @var fromname.
     */
    protected $fromname;
    
    /**
     * @var from E-mail.
     */
    protected $from;

     // {{{ __construct
    /**
     * @param E-mail for sending
     * @return WriteMail object
     */
	public function __construct($email){
		// gettingg params form config.ini
		if(is_array($email) && isset($email['target'])) $email = $email['target'];

        if(empty($email)) throw new LogException("Cant determine target e-mail form '".$email."'");
        $this->email=explode(' ',$email);
        $this->formatter=new FormatterSimple();
		$this->fromname=Config::getInstance()->mail->default_from_name;
		$this->from=Config::getInstance()->mail->default_from;
    }//}}}
   
    //{{{ write
    /**
     * Send the event's message by E-mail
     * @param  $event 
     * @return null 
	 */
    public function write($event){
		try{
			Autoload::addVendor('delayedjob');
			$towrite=$this->formatter->format($event);
			$j= new DelayedJob('sendByDJ' , array($towrite , $this->fromname , $this->from , $this->email));
			$j->priority(1)->attempts(2)->queue('logger')->add();
		}catch(Exception $e){
			throw new LogException($e);
		}
    }
    //}}}
	
	public static function sendByDJ($towrite,$fromname,$from,$email){
		try{   
            $a=Mail::CreateMail();
            $a->setSubject( "Error reporting" );
            $a->setFromname($fromname);
            $a->setFrom($from);
            foreach($email as $mail)
                $a->toAdd( $mail );
            $a->Message( $towrite );
            return $a->send();
        }catch(Exception $e){
            throw new LogException($e);
        }
	}
}//}}}
