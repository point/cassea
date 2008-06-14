<?php
class Config
{
	const ROOT_DIR = "/usr/local/www/devel";

	const STORAGE_ENGINE = "memcache";
	//const STORAGE_ENGINE = "filesystem";
	
	const IMAGES_PATH = "/usr/local/www/devel/web/images";
	const USE_IMAGES_CACHE = true;

	const JS_VER = "0.1";
	const CSS_VER = "0.1";
	
	static function get($var)
	{
		if(defined('self::'.$var))
			return constant('self::'.$var);
		return null;
	}
}
?>
