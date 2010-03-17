<?php

function split_into2col($str)
{
	$ret = array(0=>$str,1=>"");
	$str = trim($str);
	if(empty($str)) return $ret;
	$total = 0;
	$parts_t = $parts_c = array();
	foreach (preg_split('/(\r?\n){2,}/m', $str) as $part) 
	{
		$parts_c[] = $cw_part = str_word_count_utf8($part);
		$parts_t[] = $part;
		$total+=$cw_part;
	}
	$p1 = $p2 = "";
	$cur = 0;
	for($i = 0, $c = count($parts_c) - 1; $i < $c; $i++)
		if(abs(($cur + $parts_c[$i])/$total - 0.5) <
			abs(($cur + $parts_c[$i] + $parts_c[$i+1])/$total - 0.5))
		{
			$ret[0] = implode("\n\n",array_slice($parts_t,0,$i+1));
			$ret[1] = implode("\n\n",array_slice($parts_t,$i+1));
			break;
		}
		else
			$cur += $parts_c[$i];
	unset($cur);unset($parts_t);unset($parts_c);
	return $ret;
}
