<?php

Autoload::addVendor("inlinehtml");
Controller::getInstance()->onBeforeBuildWidget = array("InlineHTML","processDOM");
