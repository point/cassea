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
 * Реализация операций ввод с клавиатуры и вывода на экран.
 *
 * @version $Id$
 * @package console
 */

// {{{ IOException
/**
 * Исключение выбрасывается при ошибках в работе класса.
 *
 * @package console
 */
class IOException extends CasseaException{} // }}}

// {{{ IO
/**
 * Реализация централизованного ввода/вывода.
 *
 * Весь ввод/вывод в консоле должен происходить только по средствам 
 * этого класса.
 *
 * Основные возможности:
 * - уровни отображения и фильтрация по ним;
 * - {@link IO::out()} вывод текста, цетовое оформление по средствам esc-послдовательностеу;
 * - {@link IO::out()} вывод сообщений с заданным уровнем "шумности";
 * - {@link IO::in()} ввод значений пользоватлем: строки, символы, числа целые и дробные;
 * - {@link IO::dialog()} диалоги с вариантами ответов и ответом по умолчанию;
 * - {@link IO::outOptions()} вывод в две колонки;
 *
 * @package console
 */
class IO{

	/* Dialog Const */
	/**
	 * Вариант ответа для  {@link IO::dialog()}.
	 */
    const NONE = 32;
	/**
	 * Вариант ответа для  {@link IO::dialog()}.
	 */
    const OK = 1;
	/**
	 * Вариант ответа для  {@link IO::dialog()}.
	 */
    const CANCEL = 2;
	/**
	 * Вариант ответа для  {@link IO::dialog()}.
	 */
    const YES = 4;
	/**
	 * Вариант ответа для  {@link IO::dialog()}.
	 */
    const NO = 8;
	/**
	 * Вариант ответа для  {@link IO::dialog()}.
	 */
    const ALL = 16;

    /* Input const */
    const TYPE_INT = '1';
    const TYPE_STRING = '2';
    const TYPE_FLOAT = '3';
    const TYPE_CHAR = '4';

     /* Message const */
    const MESSAGE_FAIL = 1;
    const MESSAGE_WARN = 2;
    const MESSAGE_OK   = 3;
    const MESSAGE_TEXT = 4;
    const MESSAGE_INFO = 8;


	/**
	 * Массив отображаемых вариантов ответа.
	 * @see IO::dialog()
	 * @var array
	 */
    static private $answText = array(
        IO::NONE => "Press Enter",
        IO::OK => 'Ok',
        IO::CANCEL => 'Cancel',
        IO::YES => 'Yes',
        IO::NO => 'No',
        IO::ALL => 'All'
    );

    /**
     * Уровень отображения сообщений.
     *
     * Пользователю выводятся сообщения с уровнем не ниже $verboseLevel
     *
     * @var int 
     */
    static private $verboseLevel = IO::MESSAGE_TEXT;
    /**
     * У всех диалогов принимать ответ по умолчанию.
     *
	 * Опция коммандной строки "-y" устанавливает в true.     
	 *
     * @var bool
     */
    static private $assumeYes = false;

    /**
     * Использоваеть цветовое выделение.
     *
     * Опция "-C" устанавливает в false.     
     *
     * @var bool
     */
    static private $useColor = true;

    /**
     * Коды (замены) цветов.
     *
     * ~~~ - сбрасывает настройки цвета к начальным.
     * @var array
     */
    static private $colors = array(
        '/~~~/' => "\033[0m",
        '/~RED~/' => "\033[31m",
        '/~GREEN~/' => "\033[32m",
        '/~BROWN~/' => "\033[33m",
        '/~BLUE~/' => "\033[34m",
        '/~PURPLE~/' => "\033[35m",
        '/~CYAN~/' => "\033[36m",
        '/~SILVER~/' => "\033[37m",
        
        '/~GRAY~/' => "\033[1;30m",
        '/~WHITE~/' => "\033[1;37m",


        '/~DEF~/' => "\033[39m",
        );

    // {{{ init
    /**
     * Обрабатывает опции и устанавливает {@link $verboseLevel}, {@link $useColor} и {@link $assumeYes}.
     *
     * @param ArgsHolder $ah параметрый коммандной строки. 
     */
    static function init(ArgsHolder $ah){
        if ($ah->getOption('v')) self::$verboseLevel = IO::MESSAGE_INFO;
        if ($ah->getOption('q')) self::$verboseLevel = IO::MESSAGE_FAIL;
        if ($ah->getOption('C')) self::$useColor = false;
        if ($ah->getOption('y') || self::$verboseLevel == IO::MESSAGE_FAIL) self::$assumeYes = true;
    }// }}}

	// {{{ getVerboseLevel
	/**
	 * Возвращает уровень отображения сообщений
	 * @return int
	 */
    static function getVerboseLevel(){
        return self::$verboseLevel;
	}// }}}

	// {{{ dialog
	/**
	 * Консольный диалог
	 *
	 * Диалог формируется из трех состовляющих: сообщение $message,  варианты ответов $answ и 
	 * ответом по-умолчанию $default.
	 *
	 * Доступны варианты ответа: {@link IO::NONE}, {@link IO::OK}, {@link IO::CANCEL}, {@link IO::YES},   
	 * {@link IO::NO}, {@link IO::ALL}  в любых комбнациях.
	 * Один из вариантов ответа можно указать как ответ по умолчанию. Если он не указан, то ответом по
	 * умолчанию считает первый(младший) из вариантов ответа.
	 * При выводе варианты ответа транслируются(отображаются) в соответствии с массивом {@link IO::$answText}.
	 *
	 * Метод выводит текст сообщения, варианты ответа, среди которых цветом выделен ответ по умолчанию.
	 * После чего ожидает ввод пользователя. 
	 *
	 * Если пользователь ничего не ввел(нажал Enter) тогда принимается ответ по умолчани, 
	 * остальные варианты распознаются по первой букве в массиве {@link IO::$answText} без учета регистра.
	 * Если ответ не найден среди вариантов выбрасывается исключение.
	 *
	 * Метод возвращает выбранный вариант ответа.
	 *
	 * <code>
	 * $answ = IO::dialog('Do You really want to do  ~RED~THIS~~~?', IO::NO | IO::YES, IO::NO);
	 * if ( IO::YES == $answ ){
	 *		// let's do it
	 * }
	 * else IO::out('Cancelled... ');
	 * </code>
	 *
	 *
	 * @throws IOException
	 * @param string $message текст сообщения
	 * @param int $answ варианты ответов
	 * @param int $default ответ по умолчанию
	 * @return int выбранный вариант ответа 
	 */
    static function dialog($message, $answ = IO::NONE, $default = null){
        IO::out($message, false);
        $a = array();
        if ($answ & IO::NONE) $answ =  $default = $a[0] = IO::NONE;
        else{
            if ($answ & IO::YES) $a[] = IO::YES;
            if ($answ & IO::OK) $a[] = IO::OK;
            if ($answ & IO::NO) $a[] = IO::NO;
            if ($answ & IO::CANCEL) $a[] = IO::CANCEL;
            if ($answ & IO::ALL) $a[] = IO::ALL;
        }
        if (is_null($default)) $default = $a[0]; 
        for($i = 0, $c = count($a); $i < $c; $i++){
            $avaible[$i] = self::$answText[$a[$i]];
            if ($a[$i] == $default) $avaible[$i] = '~WHITE~'.$avaible[$i].'~~~';
        }

        $str = ' [ '.implode(' / ',$avaible).' ] ';
        IO::out($str, false);

        if (self::$assumeYes){ IO::out(null);  return $default;}

        $in = IO::in(IO::TYPE_CHAR);
        if ($answ == IO::NONE) return null;
        if (is_null($in)) return $default;

        for($i = 0, $c =count($a); $i < $c; $i++)
            if (strtolower($in) == substr(strtolower(self::$answText[$a[$i]]),0,1)) return $a[$i];
        
        throw new IOException('Incorrect input');
	}// }}}

	// {{{ out
	/**
	 * Основной метод реализующий пользовательский вывод.
	 *
	 * Метод принимает два параметра. Первый текст, который необходимо
	 * вывести, а второй параметр имеет двойственный характре.
	 * А именно: если указан bool, то он интерпритируется 
	 * как добавление переноса строки, int интерпретируется как уровень сообщения.
	 * Уровень сообщения по-умолчанию {@link IO::MESSAGE_TEXT}.
	 *
	 * Выводятся только те сообщения, уровень которых БОЛЬШЕ {@link IO::$verboseLevel}.
	 *
	 * При включенной опции {@link IO::$useColors} = true цветовые 
	 * подстановки {@link IO::$colors} в тексте сообщения заменяются на esc-последовательности,
	 * иначе игнорируются.
	 *
	 * <code>
	 * IO::out('Hello, ~GREEN~world~~~!', IO::MESSAGE_INFO);
	 * </code>
	 *
	 * @param string $message сообщение
	 * @param bool|int 
	 * @return void
	 */ 
    static function out($message = '', $nl = true){
        $type = IO::MESSAGE_TEXT;
        if (!is_bool($nl))  $type=$nl and $nl=true;
        if (self::$verboseLevel < $type) return;
        switch($type){
            case IO::MESSAGE_TEXT:break;
            case IO::MESSAGE_WARN:$message = "[ ~BROWN~WARN~~~ ] ".$message;break;
            case IO::MESSAGE_FAIL:$message = "[ ~RED~FAIL~~~ ] ".$message;break;
            case IO::MESSAGE_INFO:$message = "[ ~BLUE~INFO~~~ ] ".$message;break;
            case IO::MESSAGE_OK:$message = "[ ~GREEN~ OK ~~~ ] ".$message;break;
        }

        //process colors
        if (IO::$useColor) $r = self::$colors;
        else $r = array('/~[A-Z]+~|~~~/' =>  '');
        $message = preg_replace(array_keys($r),array_values($r), $message);
        if ($nl) $message.="\r\n";
        $stdout = fopen('php://stdout', 'w');
        fwrite($stdout, $message);
        //echo $message;
    }// }}}

	// {{{ done
	/** 
	 * Добавляет к выводу команды {@link IO::out()} надпись done зеленым цветом.
	 *
	 * Удобно использовать в контексте:
	 * <code>
	 * IO::out('продолжительная операция', false);
	 * try{
	 *		// продолжительная операция
	 *		IO::done();
	 *
	 * }
	 * catch(....
	 * </code>
	 *
	 * @param string $message
	 * @param int|bool $type
	 */
    static function done($message='', $type = IO::MESSAGE_TEXT){
        return IO::out($message."\t ~GREEN~Ok~~~",$type);
    }// }}}

	// {{{ in
	/**
	 * Ввод значения пользователем
	 * 
	 * Читает значение со стандартного потока php://stdin и 
	 * интерпретирует его согласно $type - типу ожидаемой переменной.
	 *
	 * Применяется строгое приведение типов, те любой ввод будет корректен.
	 *
	 * Возвращает приведенное к нужному типу значени, которое ввел пользователь. 
	 *
	 * @param int $type one of {@link IO::TYPE_CHAR}, {@link IO::TYPE_STRING}, 
	 *				{@link IO::TYPE_INT}, {@link IO::TYPE_FLOAT}. 
	 * @return  mixed в зависимости от типа укзанного $type	
	 */
    static function in($type = IO::TYPE_STRING){
        $stdin = fopen('php://stdin', 'r'); 
        //$line = trim(fgets($stdin)); 
        fscanf($stdin, "%s\n", $string); 
        if (is_null($string)) return null;
        switch ($type){
        case IO::TYPE_INT: $string = is_numeric($string)? 0+$string:null; break;
        case IO::TYPE_FLOAT:$string = is_numeric($string)? 0.0+$string:null; break;
        case IO::TYPE_CHAR: $string = $string[0];break;
        }
        return $string;
    }// }}}

	// {{{ outOptions
	/**
	 * Выводит ассоциативный массив в две колонке
	 *
	 * @param array $opts
	 */
    static function outOptions($opts){
        $maxKey = 0;
        $maxValue = 0;
        foreach ($opts as $k => $v){
            if ($maxKey < ($m = strlen($k))) $maxKey = $m;
            if ($maxValue < ($m = strlen($v))) $maxValue = $m;
        }

        $format = "  %-".$maxKey."s  %s";
        foreach($opts as $k => $v){
            $v = trim($v);
            if (strpos($v, PHP_EOL) === false)
                IO::out(sprintf($format, $k, $v));
            else{
                $v = explode(PHP_EOL, $v);

                IO::out(sprintf($format, $k, array_shift($v)));
                foreach($v as $l)
                    IO::out(sprintf($format,'', $l));
            }
        }
    }// }}}

	// {{{ help
	/**
	 * Информационное сообщение об опциях ввода-вывода.
	 *
	 * 
	 * @see Command::cmdHelp()
	 * @return string
	 */
    static function help(){
        return <<<HELP
~WHITE~Input/Output options~~~:
  -v    vebose output
  -q    be quiet: show only error messages, choose default answer for all questions
  -C    don't use VT colour
  -y    choose default answers for all questions

HELP;
    }// }}}
}// }}}

