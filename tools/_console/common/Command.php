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
 * Базовые класс комманды консоли: {@link Command} и {@link CommandException}.
 *
 * @version $Id: $
 * @package console
 */

/**
 * Класс исключения выбрасываемый коммандами консоли.
 *
 * Выбрасывается (в едиственном случае) когда
 * не найден файл помощи(help.txt) для комманды.
 *
 * @package console
 */
class CommandException extends CasseaException {}



/**
 * Комманда консоли.
 *
 * Реализует общие  операции по поиску инстацировнию и вызову комманд,
 * парсинга и отображения помощи, обработку общих исключений итд.
 * 
 * @package console
 */
class Command {
    // {{{ Properties
    /**
     * Имя комманды введенное в консоли
     * @var string
     */
    protected $name = '';
    /**
     * Комманда по умолчанию.
     *
     * Исполняется если не указана никакая другая подкомманда.
     * Имеет смысл для комманд с неперегруженым методом {@link Command::process}
     *
     * @var string
     */
    protected $defaultCommand = 'help';
    /**
     * Последовательность предшествующих комманд.
     * Используется для отображения "Usagе".
     * @var array
     */
    protected $commandsSeq;
    /**
     * Содержит ли комманда подкомманды(размещенные в ./commands)
     * @var boll
     */
    protected $hasSubcommands = false;
    /**
     * Working dirrectory of command
     *
     * @var string
     */
    protected $root = '';
    // }}}

    // {{{ __construct
    /**
     * Конструктор класса
     *
     * Устанавливает значения переменных класса.
     *
     * @param string $workingDir рабочая директория комманды;
     * @param array $info массив с именем комманды и коммандо по умолчанию;
     * @param array $commandsSeq  массив с предшествующими коммандами;
     */  
    public function __construct( $workingDir = '.', $info, $commandsSeq = array()){
        $this->root = $workingDir;
        $this->name = $info['name'];
        if (isset($info['default'])) $this->defaultCommand = $info['default'];
        $this->commandsSeq = $commandsSeq;
        $this->commandsSeq[] = $this->name;
        $this->hasSubcommands = count($this->getCommands());
    }// }}}

    // {{{ getCommands
    /**
     * Возвращает список доступныз подкомманд(subcommands)
     *
     * @return array 
     */  
    private function getCommands(){
        $path = rtrim($this->root, '/\\').'/commands/';
		$d = glob($path.'*', GLOB_ONLYDIR);
        $ret = array();
        foreach($d as $cmd)
            if (is_file($cmd.'/command.xml')) $ret[] = substr($cmd,strlen($path));
        sort($ret);
        return ($ret);        
    }// }}}

    // {{{ getCommandInfo
    /** 
     * Возвращает информацию о подкомманде(subcommand).
     *
     * Массив содержит имя комманды, имя класаа, который ее реализует,  подкомманду по умолчанию для подкомманды
     * и помощь.
     *
     * @throws CommandException если не найден command.xml. В текущей реализации появление исключения сдесь исключено. 
     * @return array
     */    
    private function getCommandInfo($cmd){
        $cmd_path = $this->root.'commands/'.$cmd.'/command.xml';

        if (!is_file($cmd_path)) throw new CommandException('Command '.$cmd.' not found in '.$cmd_path);
        $xml = new SimpleXMLElement(file_get_contents($cmd_path));
        $info = array();
        $val  = array('class', 'name', 'default');
        foreach($val as $v) if(isset($xml->$v)) $info[$v] = (string)$xml->$v;
        // Help
        $info['help']= $this->parseHelpFile($this->root.'commands/'.$cmd.'/help.txt');
        return $info;
    }// }}}

    // {{{ process
    /**
     * Обработка подкомманд и встроенных комманд.
     *
     * @return int результат работы (подкомманды или встроенной комманды)
     */
    public function process(){
        $cmd = ArgsHolder::get()->shiftCommand();
        if ($cmd === false) $cmd = $this->defaultCommand; 

        if (in_array($cmd, $this->getCommands()))
            return $this->processSubcommands($cmd);
        
        elseif ( false !== $this->findInclassCommand($cmd)){
            try{ return $this->processInclassCommand($cmd); }
            catch(Exception $e){
                $this->processException($e, get_class($this));
            }
        }
        else
            return $this->commandNotFound($cmd);
    }// }}}

    // {{{ findInclassCommand
    /**
     * Проверяет существование метода реализующего указнную комманду.
     *
     * @param string $cmd комманда
     * @return bool true if method callable
     */
    private function findInclassCommand($cmd){
        return is_callable(array($this,Command::cmdToMethod($cmd )));
    }// }}}

    // {{{ processInclassCommand
    /** 
     * Выполняет встроенную комманду
     * 
     * @return int результат работы встроенной комманды
     */
    private function processInclassCommand($cmd){
        if (ArgsHolder::get()->isHelp()) $this->cmdHelp($cmd);
        else
            return $this->{Command::cmdToMethod($cmd)}();
    }// }}}

    // {{{ cmdToMethod
    /**
     * Преобразовывает команду в имя метода.
     *
     * Праобразование происходит по схеме:
     * <code>
     * $cmd= 'mycommand';
     * echo cmdToMethod($cmd); // cmdMycommand
     * </code>
     *
     * @param string $cmd
     * @return string
     */
    protected static function cmdToMethod($cmd){
        return 'cmd'.strtoupper(substr($cmd,0,1)).substr($cmd,1);
    }//}}}

    // {{{ processSubcommands
    /**
     * Обработка подкомманд
     *
     * Создает объект комманды, инициализирует его и вызывает метод process()
     *
     * @param string $cmd имя подкомманды
     * @return int результат работы подкомманды
     */
    private function processSubcommands($cmd){
        //IO::out('~RED~ process Subcommand :'.$cmd.'~~~');
        $info = $this->getCommandInfo($cmd);
        $cmdRoot = $this->root.'commands/'.$cmd.'/';
        //IO::out('cmdRoot: '.$cmdRoot);
        $cmdPath = $cmdRoot.$info['class'].'.php';
        //IO::out('cmdPath: '.$cmdPath);
        //IO::out('info');
        //io::out();
        if (!is_file($cmdPath)) throw new ConsoleException('Cant find command file "'.$cmdPath.'" for command "'.$cmd.'"');
        require_once($cmdPath);
        try{
            //IO::out($info['class'].' Process');
            $cmd = new $info['class']($cmdRoot, $info, $this->commandsSeq);
            if (ArgsHolder::get()->isHelp())  
                $cmd->cmdHelp();
            else
                return  $cmd->process();
        }
        catch(Exception $e){
            call_user_func(array($info['class'],'processException'),$e, $info['class']);
        }
    }// }}}

    // {{{ parseHelpFile
    /**
     * Парсит файл справки ('help.txt') 
     *
     * Результатом является массив  для вывода через метод cmdHelp, 
     * который является встроенной коммандой help
     *
     * @throws CommandException если файл справки не найден
     * @return array 
     */
    protected function parseHelpFile($helpFile = null ){
        $helpFile = is_null($helpFile)?($this->root.'/help.txt'):$helpFile;
        if (false === ($h = file_get_contents( $helpFile)) ) throw new CommandException('help.txt for command '.get_class($this).' not found.'.PHP_EOL.'Help File: '.$helpFile);
        $h = str_replace('COMMAND_PATH', implode(' ', $this->commandsSeq), $h);
        $ha = explode(PHP_EOL, $h);
        $command = '';
        $part = '';
        $option = null;
        $help = array(
            'short' => '',
            'inclass' => array()
        );

        $help = array();
        foreach($ha as $l){            
            if (trim($l) == '' || substr($l,0,2) == '##') continue;
            if ($part = 'short') $part = 'main';

            if ( strpos($l, 'Short:')===0) {$part = 'short'; $l = trim(substr($l,strlen('Short:'))); $option=null;}
            elseif(strpos($l, 'Main:') ===0) {$part = 'main'; $l = trim(substr($l,strlen('Main:'))); $option=null;}
            elseif(strpos($l, 'Command:') ===0 &&  $command !=($newCommand = strtolower(trim(substr($l,strlen('Command:')))) )) {$command = $newCommand; $option=null; $l ='';}
            elseif(strpos($l, 'Option:') ===0 &&  $option !=($newOption = trim(substr($l,strlen('Option:'))) )){ $option = $newOption; $l='';}
            if ($l=='') continue;
            if (trim($l) == '.') $l = PHP_EOL;

            if (!is_null($option)){
                if ($l != ''){
                    if (!isset($help[$command]['options'][$option])) $help[$command]['options'][$option] = '';
                    $help[$command]['options'][$option]  .=$l.PHP_EOL;
                }
            }
            else {
                if (!isset($help[$command][$part])) $help[$command][$part] = '';
                $help[$command][$part]  .=$l.PHP_EOL;
            }
        }
        $r = $help[''];
        unset($help['']);
        $r['inclass'] = $help;

        if (empty($r['short'])) $r['short'] = '~RED~TODO~~~ Write Help in '.$helpFile;
        return $r;
    }// }}}

    // {{{ cmdHelp
    /**
     * Встроенная комманда help реализует отображение справочной информации.
     *
     * Если передедан необязательный параметр $command отображается справка
     * подкомманды.
     * 
     * @param string $command 
     * @return int 0
     */
    protected function cmdHelp($command = null){
        $help = $this->parseHelpFile();
        $subCommands =  is_null($command)?$this->getCommands():array();

        if (!is_null($command)) $help = $help['inclass'][$command];

        IO::out($help['short']);
        if (!empty($help['main'])) IO::out($help['main']);

        $list = array();
        foreach ($subCommands as $subCmd ){
            $i = $this->getCommandInfo($subCmd);
            $list[$subCmd] = trim($i['help']['short']);
        }
        if (isset($help['inclass']) && count($help['inclass']))
        foreach ($help['inclass'] as $inCmd => $v  ){
            $list[$inCmd] = trim($v['short']);
        }
        if (count($list)){
            ksort($list, SORT_STRING);
            IO::out("~WHITE~Commands~~~:");
            IO::outOptions($list);
            IO::out();
        }

        // Command Options
        if (isset($help['options']) && count($help['options'])){
            io::out('~WHITE~Options~~~:');
            io::outOptions($help['options']);
            io::out();
        }

        // Common IO Help
        IO::out(IO::help());
        return 0;
    }// }}}

    // {{{ commandNotFound
    /**
     * Метод вызываеься когда указанная пользоватлем комманда
     * не найдена (среди подкомманд и встроенных комманд)
     *
     * @param string $cmd комманда указанная пользователем
     * @return int 1
     */
    protected function commandNotFound($cmd = null){
        if ($cmd !== 'default') IO::out('Incorrect command ~WHITE~'.$cmd.'~~~', IO::MESSAGE_FAIL);
        return IO::out('Type "~WHITE~'.implode(' ',$this->commandsSeq).' help"~~~ for usage.') | 1;
    }// }}}

    // {{{ processException
    /**
     * Oбработка исключений возникших при работе комманды
     */
    static function processException($e, $cmdName = ''){
        IO::out('Command Exception ~WHITE~'.$cmdName.'~~~', IO::MESSAGE_FAIL);
        return Console::processException($e);
    }// }}}
}
