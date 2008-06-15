<?php
//
// $Id: $
//
WidgetLoader::load("WJavaScript");
//{{{ WTextareaJS
class WTextareaJS extends WJavaScript
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
        $onfocus = null,
        /**
        * @var      WJSEvent&
        */
        $onselect  = null  ;
    }
//}}}
?>
