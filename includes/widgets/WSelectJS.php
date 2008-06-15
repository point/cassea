<?php
//
// $Id: $
//
WidgetLoader::load("WJavaScript");
//{{{ WSelectJS
class WSelectJS extends WJavaScript
{
    protected

        /**
        * @var      WJSEvent&
        */
        $onblur = null,
        /**
        * @var      WJSEvent&
        */
        $onchange = null,
        /**
        * @var      WJSEvent&
        */
        $onfocus = null ;
}
//}}}
?>
