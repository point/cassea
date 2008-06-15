<?php
//
// $Id: $
//
WidgetLoader::load("WJavaScript");
//{{{ WFormJS
class WFormJS extends WJavaScript
{
    protected

        /**
        * @var      WJSEvent&
        */
        $onreset = null,
        /**
        * @var      WJSEvent&
        */
        $onsubmit = null;
}
//}}}
?>
