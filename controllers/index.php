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


class a
{
	public
		$v1 = 1;
	function seta($a)
	{
		$this->a = $a;
	}
}


?>

