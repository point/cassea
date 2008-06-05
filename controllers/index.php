<?php
function page($p1,$p2)
{
	return "index";
}
require("../includes/Controller.php");
$c = Controller::getInstance();
$c->setPageFunc('page');
$c->init();
$c->head();
echo $c->allHTML();
$c->tail();

?>

