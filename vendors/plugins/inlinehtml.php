<?php

Autoload::addDir("inlinehtml");
Controller::getInstance()->onAfterPageProcess(array("InlineHTML","processDOM"));
