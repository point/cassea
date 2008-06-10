<?php
class thetest
{
	public $ttext= "aaaaaaaaa";
	function getText($p1,$p21,$p22,$p23,$var,$const)
	{
		/*var_dump($p1);
		var_dump($p21);
		var_dump($p22);
		var_dump($p23);
		var_dump($var);
		var_dump($const);*/
		return t(new ResultSet())->forid('ttext')->set('text','vvv')->set('style','s2')->set('tooltip',"toooooltip")->set('var',1);
	}
}
?>
