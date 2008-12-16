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

require("MailObject.php");
require("MailMail.php");
require("SmtpMail.php");
require("SendmailMail.php");

/*{{{class mail
*
* Класс 'mail' содержит функцию,которая реализует фабричный метод создания 
* обьектов транспорта сообщений. 
*
*/
class Mail{
    //{{{createMail
    /**Статическая функция,реализующая фабрику обьектов транспорта сообщений.
    *  Cofig::Transport содержит тип транспорта с помощью которого будет передано 
    *  сформированное письмо.Исходя из этого создаётся соответствующий объект. 
    *  return обьект класса 'mailObject' в качестве параметра для конструктора которого передаётся 
    *  объект класса,соответствующий выбранному транспорту(с его помощью будет вызвана функция отправки
    *  выбранного транспорта)   
    *
    */
    public static function createMail(){
        $className=Config::getInstance()->mail->transport."Mail";
        try{
				if (!class_exists($className))
	        		throw new Exception("<b>Cannot find class {$className}!!!!</b> ");
                return new mailObject(new $className());;
	    }
        catch (Exception $e){echo $e->getMessage();}
    }//}}}
}
//}}}
/*
    EXAMPLE

    $a=Mail::CreateMail();
    //$a->setTexthtml();
	$a->setSubject("Тест");
	$a->setFromname("Алексей Ковтунец");
    $a->setFrom("climbonn@gmail.com");
    //$a->setFrom("skai@intvideo");
	$a->htmlMessage("Это <b>html </b> текст...");
    $a->Message("До нашего времени, криптография занималась исключительно конфиденциальностью 

Основные классические виды шифрования это перестановочное шифрование, при котором буквы 
	//$a->toAdd("point@intvideo");
	$a->toAdd("x-kai-x@rambler.ru");
	//$a->ccAdd("cccccc@fffs.com");
	//$a->BccAdd("x-kai-x@rambler.ru");
	$a->attachAdd("logo.gif");
	$a->inlineAdd("logo.gif");
    $a->attachAdd("call.php");

    $a->send();
*/
?>
