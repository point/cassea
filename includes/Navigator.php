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

// $Id$
//

// {{{ Navigator
define('MAX_PATH',20);

class Navigator
{
	private $storage = null,
			$user_path = array(),
			$controller_name = null
			;
	function Navigator($controller_name)
	{
		$this->storage = Storage::createWithSession('AdminNavigator');

		if(!isset($controller_name))
			$this->storage->un_set("user_path");
		$this->controller_name = $controller_name;

		$this->user_path = $this->storage->get("user_path");

		if(empty($this->user_path) || $this->user_path === false) $this->user_path = array();
	}
	function addStep($page_name,$title = null,$description = null)
	{
		if(!isset($page_name)) return;

		if(!isset($title))
			$title = requestURI();
		if((isset($this->user_path[0]) && $this->user_path[0]['controller'] != $this->controller_name)
			|| empty($this->user_path))
		{
			$this->user_path = array();
			$this->user_path[0]['url'] = requestURI(1);
			$this->user_path[0]['title'] = $title;
			if(isset($description))
				$this->user_path[0]['desription'] = $description;
			$this->user_path[0]['page'] = $page_name;
			$this->user_path[0]['controller'] = $this->controller_name;
			$this->storage->set("user_path",$this->user_path);
			return;
		}
		if($this->user_path[0]['page'] == $page_name)
		{
			$this->user_path[0]['url'] = requestURI(1);
			$this->user_path[0]['title'] = $title;
			if(isset($description))
				$this->user_path[0]['desription'] = $description;
			$this->user_path[0]['page'] = $page_name;
			$this->user_path[0]['controller'] = $this->controller_name;
			$this->storage->set("user_path",$this->user_path);
			return;
		}
		for($i = count($this->user_path) - 1; $i >= 0 ; $i--)
		{
			if($this->user_path[$i]['page'] == $page_name)
			{
				$this->user_path[$i]['url'] = requestURI(1);
				if(isset($title))
					$this->user_path[$i]['title'] = $title;
				if(isset($description))
					$this->user_path[$i]['desription'] = $description;
				$this->user_path[$i]['page'] = $page_name;
				$this->user_path[$i]['controller'] = $this->controller_name;
				for($j = $i-1; $j >= 0; $j--)
					unset($this->user_path[$j]);
				$this->user_path = array_values($this->user_path);
				$this->storage->set("user_path",$this->user_path);
				return;
			}
		}
		if(count($this->user_path) == MAX_PATH)
		{
			unset($this->user_path[MAX_PATH - 1]);
			$this->user_path=array_values($this->user_path);
		}

		array_unshift($this->user_path,
			array(
				"url" => requestURI(1),
				"title" => isset($title)?$title:null,
				"description" => isset($description)?$description:null,
				"page"=>$page_name,
				"controller"=>$this->controller_name));

		$this->storage->set("user_path",$this->user_path);
	}
	function getStep($step)
	{
		$step = abs($step);
		if($step >= count($this->user_path)) return isset($this->user_path[0])?$this->user_path[0]:"";
		return $this->user_path[$step];
	}
    function getStepURL($step)
    {
        $s = $this->getStep($step);
        return isset($s['url'])?$s['url']:"";
    }
	function getAdminStep($step)
	{
		if($step >= count($this->user_path)) return $this->user_path[0]['url'];
		return $this->user_path[$step]['url'];
	}
	function getSteps()
	{
		return array_reverse($this->user_path);
	}
	function clean()
	{
		$this->storage->un_set("user_path");
	}
	function setTitle($url,$title)
	{
		if(empty($url) || !isset($title)) return;
		for($i = 0; $i < count($this->user_path);$i++)
			if($this->user_path[$i]['url'] == $url)
				$this->user_path[$i]['title'] = Filter::filter($title,Filter::STRING_QUOTE_ENCODE);
		$this->storage->set("user_path",$this->user_path);
	}
}
// }}}
?>
