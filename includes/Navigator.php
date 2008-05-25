<?php
// {{{ Navigator
/**
*
*
*/
define('MAX_PATH',20);

class Navigator
{
	private $storage,
			$user_path
			;
	function Navigator($controller_name)
	{
		$this->storage = Storage::create('AdminNavigator');

		if(!isset($controller_name))
			$this->storage->un_set("user_path");

		$this->user_path = $this->storage->get("user_path");
		if(empty($this->user_path)) $this->user_path = array();
	}
	function my_array_push(&$arr,$var)
	{
		if(empty($arr)) {$arr[0] = $var;return;}
		for($i = count($arr)-1; $i >= 0 ;$i--)
			$arr[$i+1] = $arr[$i];
		$arr[0] = $var;
	}
	function addStep($url,$title,$description)
	{
		if(strpos($url,"page=")===false || strpos($url,"package=")===false)
		{
			$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,
				"Parameter 'url' doesn't have 'page' component"),LOG_LEVEL_WARNING);
			return;
		}
		if($this->step_added) return;
		$this->step_added = 1;
		preg_match("/[&?]page=([^&?#]+)/",$url,$m);
		$cur_page = $m[1];unset($m);
		preg_match("/[&?]package=([^&?#]+)/",$url,$m);
		$cur_package = $m[1];
		if($this->user_path[0]['package'] != $cur_package)
		{
			unset($this->user_path);
			$this->user_path[0]['url'] = $url;
			$this->user_path[0]['title'] = $title;
			$this->user_path[0]['desription'] = $description;
			$this->user_path[0]['page'] = $cur_page;
			$this->user_path[0]['package'] = $cur_package;
			$this->storage->set("user_path",$this->user_path);
			return;
		}
		if($this->user_path[0]['page'] == $cur_page)
		{
//print_pre($this->user_path);
			$this->user_path[0]['url'] = $url;
			$this->user_path[0]['title'] = $title;
			$this->user_path[0]['desription'] = $description;
			$this->user_path[0]['page'] = $cur_page;
			$this->user_path[0]['package'] = $cur_package;
			$this->storage->set("user_path",$this->user_path);
/*echo "1";
print_pre($this->user_path);*/
			return;
		}
		for($i = count($this->user_path) - 1; $i >= 0 ; $i--)
		{
			if($this->user_path[$i]['page'] == $cur_page)
			{
				$this->user_path[$i]['url'] = $url;
				$this->user_path[$i]['title'] = $title;
				$this->user_path[$i]['desription'] = $description;
				$this->user_path[$i]['page'] = $cur_page;
				$this->user_path[$i]['package'] = $cur_package;
				for($j = $i-1; $j >= 0; $j--)
				{
					unset($this->user_path[$j]);
				}
				$this->user_path = array_values($this->user_path);
				$this->storage->set("user_path",$this->user_path);
/*echo "2";
print_pre($this->user_path);				*/
				return;
			}
		}
		if(count($this->user_path) == MAX_PATH)
		{
			unset($this->user_path[MAX_PATH - 1]);
			$this->user_path=array_values($this->user_path);
		}

		$this->my_array_push($this->user_path,
			array(
				"url" => $url,
				"title" => $title,
				"description" => $description,
				"page"=>$cur_page,
				"package"=>$cur_package));
/*echo "3";
print_pre($this->user_path);				*/
		$this->storage->set("user_path",$this->user_path);
	}
	function getStep($step)
	{
		return $this->user_path[$step];
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
		$site_user = &User::get();
		$storage = new CVarStorage("AdminNavigator", $site_user->get_session_id(), time()+ 1000000);
		$storage->unset_("user_path");
	}
	function setTitle($url,$title)
	{
		if(empty($url) || !isset($title)) return;
		Filter::apply_filter(7,$title);
		for($i = 0; $i < count($this->user_path);$i++)
			if($this->user_path[$i]['url'] == $url)
				$this->user_path[$i]['title'] = $title;
		$this->storage->set("user_path",$this->user_path);
	}
}
// }}}
?>
