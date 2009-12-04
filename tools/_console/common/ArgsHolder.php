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
 * Обработка аргументов коммандной строки.
 *
 * Работа с коммандами и опциями ведется посредствам класса {@link ArgsHolder}.
 *
 * @package console
 */

// {{{ ArgsHolderException
/**
 * Класс исключения выбрасыаемый парсером аргументов 
 *
 * @package console
 */
class ArgsHolderException extends CasseaException{}// }}}

// {{{ ArgsHolder
/**
 * Парсер аргументов коммандной строки.
 *
 * Концепуия работы консоли состоит в том, что комманды переданные из коммандной строки
 * обрабатываются одна за одной и следующая комманда является подкоммандой предыидущей.
 * Например:<b>group list</b>. Комманда <b>list</b> является подкоммандой
 * комманды <b>group</b>.
 * 
 * Обработка комманд происходит {@link ArgsHolder::shiftCommand выталкиванием первой из списка комманд} и инстанцированием
 * сообветствующего объекта(подкомманда) или вызова метода(встроенная комманда), 
 * которые в свою очередь могут выталкивать еще одну комманду из списка и производить 
 * соответствующие действия(инстанцировать класс или вызывать метод) итд.
 *
 * Комманды удовлетворяют выражению {@link ArgsHolder::COMMAND}.
 * По умолчанию содержать только буквы и цифры.
 *
 * Опуии могут быть простые: -q, -v, --veryquiet, 
 * и со значением: -o=color, --user_chanel=cassea;
 * Опуии типа "-o option_value" не разбираются (точнее парсятся как опция "о" и команда "option_value").
 * Реализовано объединение простых опций: -qy(эквивалентно -q -y), -vf=file(эквивалентно -v -f=file)
 *
 * Если параметр коммандной строки не соответствует кооманде или опции выбрасывается исключение 
 * {@link ArgsHolderException}. Для передачи строковых значений,  
 * содержащие символы не соответсвующие {@link ArgsHolder::COMMAND}, 
 * можно воспользоватся опциями со значением.
 *
 * Расширять выражением {@link ArgsHolder::COMMAND} не реккомендуется.
 * 
 * @version $Id$
 * @package console
 */
class ArgsHolder{
    /**
     * Регулярное выражение для комманды
     */
    const COMMAND = '#^[a-zA-Z0-9.@_/~:-=<>]+$#';
    /**
     * Объект ArgsHolder
     *
     * @var ArgsHolder
     */
    static private  $instance = null;
    /**
     * Ассоциативный массив опций.
     *
     * @var array
     */
    private $options = array();
    /**
     * Массив комманд в порядке появления их в коммандной строке
     *
     * @var array
     */
    private $commands = array();

     // {{{ get
    /**
     * Реализация singleton
     *
     * @return ArgsHolder
     */
    static function get(){
        if (!is_object(self::$instance))
            self::$instance = new ArgsHolder();
        return self::$instance;
    }//}}}

    // {{{ ArgsHolder
    /**
     * Конструктор класса полностью реализует 
     * разбор аргументов коммандной строки.
     *
     * Сохраняет комманды и опции в свойства класса.
     */ 
    private function ArgsHolder(){
        $args = isset($argv)?$argv:
            isset($_SERVER['argv'])?$_SERVER['argv']:null;
        if (!is_array($args) || !count($args))
            throw new ArgsHolderException('Unable get command line arguments');
        array_shift($args);
        foreach($args as $arg)
            if (preg_match(self::COMMAND, $arg)) $this->commands[] = $arg; 
            elseif( preg_match('#^--([a-zA-Z0-9-]+)(=(.*))?$#', $arg, $m)){ 
                if(isset($m[2])) $this->options[$m[1]] = $m[3];
                else $this->options[$m[1]] = true;
            }
            elseif( preg_match('#^-([A-Za-z0-9]*)([a-zA-Z0-9])(=(.*))?$#', $arg, $m)){
                if(isset($m[3])) $this->options[$m[2]] = $m[4];
                else $this->options[$m[2]] = true;
                if($c = strlen($m[1])) for($i = 0; $i < $c; $i++) 
                    $this->options[$m[1][$i]] = true;
            } 
            else throw new ArgsHolderException('Bad formed command line argument: "'.$arg.'"');
    }// }}}

    // {{{ getOptions
    /**
     * Возвращает текущий массив опций
     *
     * @return array
     */ 
    function getOptions(){
        return $this->options;
    }// }}}

    // {{{ getCommands
    /**
     * Возвращает текущую очередь комманд
     *
     * @return array
     */
    function getCommands(){
        return $this->commands;
    }// }}}

    // {{{ shiftCommand
    /**
     * Выталкивает первую команду из очереди.
     * Если очередь пуста возвращает false
	 *
	 * @param bool $lowerCase  приводить имя к нижнему регистру
     * @return string|bool имя комманды, false если очередь пуста
     */
    function shiftCommand($lowerCase = true){
		if (count($this->commands)){
			$command = array_shift($this->commands);
			return $lowerCase?strtolower($command):$command;
		}
        return false;
    }//}}}

    // {{{ isHelp
    /**
     * проверяет является ли превая комманда в очереди коммандой 'help'
     * 
     * @return bool
     */
    function isHelp(){
        return isset($this->commands[0]) && $this->commands[0] == 'help';
    }//}}}

    // {{{ getOption
    /**
     * Возвращает значение опции $option.
     *
     * Для простых опций ( -q, -v) возвращает true;
     * для опций со значениями ( -file=text.doc ) - строковое значение
     *  
     * Параметр $shift указывает что опция будет удалена из списка опций.
     * Это необходимо для получения списка неверных(неиспользованных) опций.
     *
     * Если опция $option не указанна функция вернет false
     *
     * @param string    $option
     * @param bool      $remove
     * @return mixed
     */
    function getOption($option, $remove = true){
        if (!isset($this->options[$option])) return false;
        $ret = is_null($this->options[$option])?true:$this->options[$option];
        if ($remove) unset($this->options[$option]);
        return $ret;
    }// }}}
}
