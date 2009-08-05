<?php
/** 
 * Обработка акргументов коммандной строки.
 *
 * Опуии могут быть простые: -q, -v, --veryquiet, 
 * и со значением: -o=color, --user_chanel=cassea;
 * Опуии типа "-o option_value" не разбираются (точнее парсятся как опция "о" и команда "option_value").
 * Реализовано объединение простых опций: -qy(эквивалентно -q -y), -vf=file(эквивалентно -v -f=file)
 */

class ArgsHolderException extends CasseaException{

}

/**
 * Парсек аргументов коммандной строки.
 *
 * Реализован синглтоном.
 * <code>
 * $ah = ArgsHolder::get();
 * </code>
 *
 * Основные функции доступа к коммандам и опциям - shiftCommand() и getOption() соответственно. 
 *
 */
class ArgsHolder{
    static private  $instance = null;
    private $args;
    private $options = array();
    private $commands = array();

    static function get(){
        if (!is_object(self::$instance))
            self::$instance = new ArgsHolder();
        return self::$instance;
    }


    private function ArgsHolder(){
        $args = isset($argv)?$argv:
            isset($_SERVER['argv'])?$_SERVER['argv']:null;
        if (!is_array($args) || !count($args))
            throw new ArgsHolderException('Unable get command line arguments');
        array_shift($args);
        $this->args = $args;
        foreach($args as $arg)
            if (preg_match('#^[a-zA-Z0-9.@_]+$#', $arg)) $this->commands[] = strtolower($arg); 
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
    }

    function getOptions(){
        return $this->options;
    }

    function getCommands(){
        return $this->commands;
    }

    // {{{ shiftCommand
    /**
     * Выталкивает первую команду из очереди.
     * Если очередб пуста возвращает false
     *
     * @return mixed string имя комманды, false если очередь пуста
     */
    function shiftCommand(){
        if (count($this->commands))
            return array_shift($this->commands);
        return false;
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
     */
    function getOption($option, $remove = true){
        if (!isset($this->options[$option])) return false;
        $ret = is_null($this->options[$option])?true:$this->options[$option];
        if ($remove) unset($this->options[$option]);
        return $ret;
    }// }}}
}
