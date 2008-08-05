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
			->forid("hidden")->set("value","qwe2")->end()
			->forid("ta")->set("value","qqqq")->end()
			->forid("href")->set("href","http://www.google.com")
			->child("google")->set('text',"www.google.com")
			->forid("tabs")->child("test3")->set("href","http://devel/phpinfo/")

			->forid("list")->child("li")->each(0)->set('text','list item 0')
									->each(1)->set('text',"list item 1")
									->each(2)->set('text',"list item 2")

			;
	}
	function getList()
	{
		return t(new Result())
			->forid("li")->each(0)->set('text','li0')->each(1)->set('text',"li1");
	}
	function roll()
	{
		$r = t(new Result())
			->forid("roll_text");
		for($i = 0; $i < 20; $i++)
			$r->each($i)->set("text","text".($i+1));
		return $r;
	}
}
?>
