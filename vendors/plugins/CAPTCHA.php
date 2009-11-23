<?php

Autoload::addVendor('captcha');
Controller::getInstance()->onBeforeCheckByRules = array('captchaCheck','check');

?>
