<?php

require_once(Config::get("root_dir")."/vendors/captcha/captchaCheck.php");
Controller::getInstance()->onBeforeCheckByRules = array('captchaCheck','check');

?>
