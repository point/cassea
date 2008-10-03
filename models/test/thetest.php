<?php
/*- vim:expandtab:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008, Cassea Project
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*     * Redistributions of source code must retain the above copyright
*       notice, this list of conditions and the following disclaimer.
*     * Redistributions in binary form must reproduce the above copyright
*       notice, this list of conditions and the following disclaimer in the
*       documentation and/or other materials provided with the distribution.
*     * Neither the name of the Cassea Project nor the
*       names of its contributors may be used to endorse or promote products
*       derived from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY CASSEA PROJECT ''AS IS'' AND ANY
* EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL CASSEA PROJECT BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
}}} -*/

class thetest
{
	//public $ttext= "aaaaaaaaa";
	function getText($p1,$p2,$p3,$const)
	{
		/*var_dump($p1);
		var_dump($p2);
		var_dump($p3);
		var_dump($const);
		var_dump($limit);*/

		//return t(new ResultSet())->f('wtext')->set('text','vvv2')->set('tooltip','toooooltip');

		//return t(new ResultSet())->f('#ttext:contains(qwe):visible')->text('vvv6');

		return t(new ResultSet())->f('#ttext + wtext')->text('vvv6')->q(1)
			->f("#block2  wtext:nth-child(3)")->text('vvv7')
			//->f("wbutton")->value('button')
			->f("wcheckbox")->text('checkbox')
			//->f("wedit")->value('edit')->size(10)
			->f("#edit_roll")->count(2)
			->f("#edit1",0)->additional_id(10)
			->f("#edit1",1)->additional_id(11)
			->f("wfieldset")->legend('legend')
			//->f("whidden")->value('hidden')
			->f("wimage")->src("01.jpg")
			->f("wradio")->text('radio')
			->f("wspinner")->size(10)
			->f("wtabs wtab:first-child")->set('tab_title','tab 1');
		//return t(new ResultSet())->f('wblock > #ttext,#yyy2')->text('vvv6');
	}
	/*function getList()
	{
		return t(new Result())
			->forid("li")->each(0)->set('text','li0')->each(1)->set('text',"li1");
	}*/
	function roll($limit=0)
	{
		return t(new ResultSet())->f("#roll")->set('count',10)
			->f("#roll2")->count(2)
			/*->f("#roll wtext:index(last)")->text('in the roll index last')
			->f("#roll2:index(last) wtext:index(last)")->text('at last!')
			->f("#roll2:index(3) wtext:index(last)")->text('3')*/
			//->f('#roll2:index(last:local) wtext:index(first)')->text('local last')
			->f("#roll2 wtext",0)->text('q')

			;
			//->f("#roll2")->count(2);;
	}
	static function staticTest()
	{
	}
	function checkText($post,$p1,$p2,$p3,$const)
	{
		echo "CHECKER";
		print_pre($post);
		print_pre($p1);
		print_pre($p2);
		print_pre($p3);
		var_dump($const);
	}
	function handleText($post)
	{
		echo "HANDLER";
		print_pre($post);
	}
	function finilize()
	{
		echo "FINILIZE";
		die("QQQQ");
	}
	function checkLogin($post)
	{
		echo "CHECK LOGIN";
		print_pre($post);
		if($post->login == "qwe")
			throw new CheckerException("Fill login,FF","login");
	}
	function setLogin($val)
	{
		echo "LOGIN";
		var_dump($val);

	}
}
?>
