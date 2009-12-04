<?php
/**
 * Задача выводит сообщение.
 *
 * 
 *
 *
 */
class EchoTask extends DeployTask{
    // {{{ deploy
    /**
     * Функция развертывания
     *
     * В случае ошибок во время развертывния должна выбрасывать исключения.
     */
    function deploy(){
        $attr = $this->node->attributes;
        if (!is_null($text = $attr->getNamedItem('deploy')))
            io::out($text->nodeValue);
        if (!is_null($text = $attr->getNamedItem('undeploy')))
            file_put_contents($this->rollbackDir->getFile('message'), $text->nodeValue);
    }// }}}


    // {{{ undeploy
    /**
     * Откат задачи
     *
     * @param Dir $dir директория в которой были сохранены откаты
     */
    static function undeploy($dir){
        $f = $dir->getFile('message');
        $f->exists()?io::out(file_get_contents($f)):null;

    } // }}}
}
