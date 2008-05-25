<?php
class Config
{
	const ROOT_DIR = "/usr/local/www/devel";

	const STORAGE_ENGINE = "memcache";
	//const STORAGE_ENGINE = "filesystem";
	
	static function get($var)
	{
		if(defined('self::'.$var))
			return constant('self::'.$var);
		return null;
	}
}
?>
