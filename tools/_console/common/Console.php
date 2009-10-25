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
 * Исполняемый файл консоли
 *
 * Cоздает объект класса {@link Console}.
 * Затем инициализирует его и вызывает метод {@link Console::process()}.
 *
 * Результат выполнения возвращается в оболочку как код завершения.
 *
 * @version $Id: $
 * @package console
 */

/**
 * Установка необходимых настроек PHP
 */
ini_set('include_path', dirname(dirname(__FILE__)).'/');
ini_set('display_errors', 1);
ini_set('error_reporting', 1);
error_reporting(E_ALL | E_STRICT);

/**
 * Подключение необходимых классов
 */
require_once(dirname(__FILE__)."/../../../includes/Boot.php");
require_once('ArgsHolder.php');
require_once('IO.php');
require_once('Command.php');

// {{{ ConsoleException
/**
 * Исключение выбрасывается при ошибках в работе консоли(но не комманд).
 *
 * @package console
 */
class ConsoleException extends CasseaException {}// }}}

// {{{ Console
/**
 * Консоль
 *
 * Выполняет необходимую {@link Console::Init() инициализацию}, 
 * предоставляет {@link Console::initCore() метод для инициализации
 * движка} и {@link Console::processException() метод обработки ошибок}.
 *
 *
 * @version $Id:$
 * @package console
 */
class Console{
    /**
     * Имя файла выполняемого в коммандной оболочке(shell).
     * Необходимо для корректного отображения 'Usage'-строк.
     */
    const CMD = 'console';

    /**
     * Информация о комманде.
     * @var array
     */
    private $cmdConsoleInfo = array(
        'name' => Console::CMD
        );

    // {{{ Init
    /**
     * Необходимая инициализация и проверки(TODO)
     */
    public function Init(){
        // TODO првоерка параметров php и переменных среды
        //output_buffering=0 \
        // basedir&
        // safe_mode
        //  register_argc_argv="On" \
        // auto_prepend_file="" \
        // auto_append_file="" \
        // error_reporting(0);

        IO::init(ArgsHolder::get());
    }// }}}

    // {{{ process
    /**
     * Выполняет первую комманду из коммандной строки.
     *
     * Если комманды не указанны то выводится информационное сообщение.
     */
    public function process(){
        if (!count(ArgsHolder::get()->getCommands())) return IO::out('Type "~WHITE~'.Console::CMD.' help"~~~ for usage.');
        $cmdConsole = new Command(dirname(dirname(__FILE__)).'/', $this->cmdConsoleInfo);
        return $cmdConsole->process();
    }// }}}

    // {{{ processException
    /**
     * Обработка исключений, возникающих в процессе работы консоли.
     *
     * @param Exception $e исключение для обработки
     */
    static function processException($e){
        if ($e instanceof IOException ) IO::out('IO error : '.$e->getMessage(), IO::MESSAGE_FAIL);
        else IO::out($e, IO::MESSAGE_FAIL);
        exit(254);
    }//}}}

    // {{{ initCore
    /**
     * Создает окружение для работы.
     *
     * Подключает классы и файлы,
     * инициализирует основные подсистемы движка(базу данных, конфигурацию итд).
     */
    static function initCore(){
        static $isConnected = false;
        if ($isConnected) return;
        $v = IO::getVerboseLevel();
        if ($v > IO::MESSAGE_TEXT ) IO::out('Connecting to Cassea...', false);
		/*
		 * point. TODO: remove due to Boot bootstrapper is used.
		 * try{
            Controller::makeEnv();
        }
        catch(Exception $e){
            self::processException($e);
		}*/
        if ($v > IO::MESSAGE_TEXT ) IO::done();
        $isConnected = true;
    }// }}}

}// }}}

/**
 * Выполнение
 */
try {
	$c =  new Console();
    $c->Init();
    $r =  $c->process();
    exit( $r );
}
catch (CasseaException $e){
    Console::getInstance()->processException($e);
}

