<?php
require_once ("Console.php");

$c = Console::getInstance();
try {
    $c->Init();
    $c->process();
}
catch (Exception $e){
    Console::getInstance()->processException($e);
}

