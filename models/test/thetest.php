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
		return t(new Result())->forid('ttext')->set('text','vvv')->set('style','s2')->set('tooltip',"toooooltip")->set('strong',1)
			->forid("test_radio")->set("text","radio text2")->set('checked','1')
			->forid("hidden")->set("value","qwe2");
	}
}
?>
