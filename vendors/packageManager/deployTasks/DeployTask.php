<?php
/**
 * Абстрактный класс задачи развертывания
 *
 */
abstract class DeployTask{
    /**
     * XML node содержащий информацию о задача
     * @var DOMNode
     */
    protected $node;
    /**
     * Распакованный пакет.
     * @var Dir
     */
    protected $dir;
    /**
     * Диреткория отката
     * @var Dir
     */
    protected $rollbackDir;

    // {{{ __construct
    /**
     * Конструктор
     *
     * @param Dir $buildpad
     */
    function __construct($buildpad = null){
        $this->dir = $buildpad;
    }// }}}

    // {{{ setRollbackDir
    /**
     * Установка директории для сохранения откатов
     *
     * @param Dir $dir
     * @return DeployTask $this
     */
    function &setRollbackDir(Dir $dir){
        $this->rollbackDir = $dir;
        return $this;
    }// }}}

    // {{{ getRollbackDir
    /**
     * Возвращае директорию для сохраненияоткатов
     *
     * @return Dir
     */
    final function getRollbackDir(){
        return $this->rollbackDir;
    }// }}}


    // {{{ setParam
    /**
     * Установка параметров задачи ввиде xml узла из файла package.xml
     *
     * @param DOMNode $tag
     * @return DeployTask $this
     */  
    final function &setParam(DOMNode $tag){
        $this->node = $tag;
        return $this;
    }// }}}

    // {{{ deploy
    /**
     * Функция развертывания
     *
     * В случае ошибок во время развертывния должна выбрасывать исключения.
     */
    function deploy(){
    }// }}}


    // {{{ undeploy
    /**
     * Откат задачи
     *
     * @param Dir $dir директория в которой были сохранены откаты
     */
    static function undeploy($dir){
    } // }}}
}
