<?php
class thetest
{
	public $ttext= "aaaaaaaaa";
	function getText()
	{
		return t(new ResultSet())->forid('ttext')->set('text',"vvvv");
	}
}
?>
