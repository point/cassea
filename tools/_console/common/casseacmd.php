<?php
require_once ("Console.php");
$c = Console::getInstance();
try {
    $c->Init();
    $r =  $c->process();
    exit( $r );
}
catch (CasseaException $e){
    Console::getInstance()->processException($e);
}

