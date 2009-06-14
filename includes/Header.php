<?php
/*- vim:noet:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008,2009 Cassea Project
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*     * Redistributions of source code must retain the above copyright
*       notice, this list of conditions and the following disclaimer.
*     * Redistributions in binary form must reproduce the above copyright
*       notice, this list of conditions and the following disclaimer in the
*       documentation and/or other materials provided with the distribution.
*     * Neither the name of the Cassea Project nor the
*       names of its contributors may be used to endorse or promote products
*       derived from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY CASSEA PROJECT ''AS IS'' AND ANY
* EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL CASSEA PROJECT BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
}}} -*/


// $Id$
// {{{ Header
class Header
{
        /* Redirection 3xx */
        const MULTIPLE_CHOICES = 300;
        const MOVED_PERMANENTLY = 301;
        const FOUND = 302;
        const SEE_OTHER = 303;

        /* Client Error 4xx */
        const BAD_REQUEST = 400;
        const UNAUTHORIZED = 401;
        const PAYMENT_REQUIRED = 402;
        const FORBIDDEN = 403;
        const NOT_FOUND = 404;

		const INTERNAL_SERVER_ERROR = 500;
		const SERVICE_UNUNAVAILABLE = 503;

        /* Title build order */
        const TITLE_BUILD_ORDER_NATIVE = 0;
        const TITLE_BUILD_ORDER_REVERSE = 1;

        private static $statusText =  array(
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',

            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found'
        );


        /**
        * Заголовки по умолчанию.
        * В данной версии используются заголовки против кэширования страницы
        * @var      array
        */
        private $headers = array();
        /**
        * Текущий DOCTYPE
        * @var      string
        */
        private $doctype = null;
        /**
        * Массив с предопределенными DOCTYPES
        * @var      array
        */
		private $predefine_doctypes = array (
			''  => '',
            'strict'                => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
            'transitional'          => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
            'frameset'              => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">',
            'xhtml11'               =>'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
            'xhtml10strict'         =>'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
            'xhtml10transitional'   =>'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
            'xhtml10frameset'       =>'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'
            );
        /**
        * Заголовок документа
        * @var      string
        */
        private $title = null;
        private $titleStart = null;
        private $titleEnd = null;
        private $titleSeparator = ' ';
        private $titleItems = array();
        /**
         * Порядок формирования заголовка документа из елементов
         */
        private $titleBuildOrder = Header::TITLE_BUILD_ORDER_NATIVE;
        
        /**
        * Описание документа
        * @var      string
        */
        private $description = null;
        /**
        * Массив сета елементов
        * @var      array
        */
        private $meta = array();
        /**
        * SEO robots index, noindex
        * @var      array
        */
        var $robots_noindex = false;
        /**
        * SEO robots index, nofollow
        * @var      array
        */
        var $robots_nofollow = false;
        /**
        * Массив ссылок
        * @var      array
        */
        var $links = array();
                /**
        * Массив елементов <Script>
        * @var      array
        */
        var $scripts = array();

    /** {{{ __construct
    */
    protected function __construct ($doctype = 'strict')
	{
        $this->setDoctype($doctype);

        if(Config::get('x_ua_compatible')== 'on')
            if(substr($doctype,0,5)=='xhtml')
                header('X-UA-Compatible: IE=EmulateIE7');
            else
                $this->addMeta(array('http-equiv'=>'X-UA-Compatible','content'=>'IE=EmulateIE7'));
	}// }}}

    /** {{{ get
    * Возвращает ссылку на объект Заголовока  документа
    * @return   Header
    */
    static function get()
    {
        static $instance = null;
        if (!isset($instance))
           $instance = new Header();             
        return $instance;
    }// }}}

    /** {{{ addHeader
    * @param    string $headers    Стока
    * @return   void
    */
    function addHeader($header)
	{
		if(!isset($header)) return;
		$this->headers[] = $header;
    }// }}}
    
    /** {{{ redirect
    * Переадресация клиента
    *
    * RFC 2616, 10.3.4
    * RFC 2616, 10.3.3
    *
    * @param    string $url    Адрес пересылки
    * @return   void
    */
    static function redirect( $url, $code = 302 )
    {
        header($_SERVER['SERVER_PROTOCOL'].' '.$code.' '.self::$statusText[$code]); 
        if (!strpos($url, '://'))  $url = Header::makeHTTPHost().$url;
        header('Location: '.$url);

        echo <<<GO
            <html><head><title>Redirect</title></head><body><p><p> 
            HTTP redirect (status code: $code ).<br> 
            Follow the <a href="$url">"White Rabbit"</a>. 
            </body></html> 
GO;
        die();
    }// }}}

    /** {{{ makeHTTPHost
     * Определение baseUrl: протокол, хост, порт
     * @return string
     */
    static public function makeHTTPHost(){
        return (empty($_SERVER['HTTPS'])?'http://':'https://').
            $_SERVER['HTTP_HOST'].
            (($_SERVER['SERVER_PORT'] != 80)?":".$_SERVER['SERVER_PORT']:"");
    }// }}}

    /** {{{ error
     * Генерация заголовков ошибок.
     */
    static function error($err = Header::NOT_FOUND){
        header("HTTP/1.0 ".$err." ".self::$statusText[$err]);
        if ( $err >=400 ) for ( $i = 0; $i< 20; $i++) echo '<!--=================================IE Pad=========================================-->';
        exit(); 
    }// }}}

    /** {{{ setDoctype
    * Функция устанавливает один из предустанвленных DOCTYPEов  документа:
    * Strict,
    * Transitional,
    * Frameset;
    * @param    string $type    
    * @return   void
    */
    function setDoctype($type = '')
	{
		$type = strtolower($type);
        if (!in_array( $type, array_keys( $this->predefine_doctypes ))) return;
        $this->doctype = $type;
    }// }}}

    // {{{ ==== Title ====

    /** {{{ setTitle
    * Функция устанавливает поле title в заголовке
    * @param    string $title    
    * @return   void
    */
    function setTitle($title = "")
	{
		if(is_string($title)  && !empty($title) && strlen($title) <= 255)
			$this->title = $title;
    }// }}}

    function getTitle(){
        if (!empty($this->title)) return $this->title;;
        $t = $this->generateTitle();
        if (!empty($t)) return $t;
        return null;
    }

    function generateTitle(){
        $arr = $this->titleItems;
        if (!is_null($this->titleStart))array_unshift($arr, $this->titleStart);
        if (!is_null($this->titleEnd))array_push($arr, $this->titleEnd);
        if ($this->titleBuildOrder == Header::TITLE_BUILD_ORDER_REVERSE) $arr = array_reverse($arr);
        return implode($this->titleSeparator, $arr);
    }



    /** {{{ setTitleStart
    * Функция устанавливает первый елемент заголовка страницы
    * @param    string $titleStart
    * @return   void
    */
    function setTitleStart($titleStart = "")
	{
		if(is_string($titleStart)  && !empty($titleStart) && strlen($titleStart) <= 255)
			$this->titleStart = $titleStart;
          
    }// }}}

    /** {{{ setTitleEnd
    * Функция устанавливает последний елемент заголовка страницы
    * @param    string $titleEnd
    * @return   void
    */
    function setTitleEnd($titleEnd = "")
	{
		if(is_string($titleEnd)  && !empty($titleEnd) && strlen($titleEnd) <= 255)
			$this->titleEnd = $titleEnd;
          
    }// }}}

    /** {{{ setTitleSeparator
    * Функция устанавливает разделитель между елементами заголовка старницы
    * @param    string $titleSeparator 
    * @return   void
    */
    function setTitleSeparator($titleSeparator = "")
	{
		if(is_string($titleSeparator)  && !empty($titleSeparator) && strlen($titleSeparator) <= 255)
			$this->titleSeparator = $titleSeparator;
          
    }// }}}

    /** {{{ setTitleBuildOrder
    * Функция устанавливает порядок формирования заголовка страницы из елементов 
    * @param    string $titleSeparator 
    * @return   void
    */
    function setTitleBuildOrder($order = Header::TITLE_BUILD_ORDER_REVERSE)
    {
        if ($order == Header::TITLE_BUILD_ORDER_REVERSE || $order == Header::TITLE_BUILD_ORDER_NATIVE)
			$this->titleBuildOrder = $order;
    }// }}}


    /** {{{ addTitleItem
    * Функция добавляет в конец списка елементов заголовока заданную строку.
    * 
    * Функция необходима для создания заголовка страницы по типу:
    * :: главная :: каталог :: мобильные телефоны ::
    * @param    string $item  
    * @return   void
    */
    function addTitleItem($item = "")
    {
		if(is_string($item)  && !empty($item))
			$this->titleItems[] = $item;
    }// }}}

    // }}} ==== Title ====

    /** {{{ setDescription
    * Устанавливает описание документа
    * @param    string $descr    
    * @return   boolean
    */
    function setDescription($descr)
    {
		if(is_string($descr)  && !empty($descr))
			$this->description = $descr;          
    }// }}}

    /** {{{ addToDescription
    * Добавляет к существующему описанию заданную строку
    * @param    string $descr    
    * @return   boolean
    */
    function addToDescription($descr)
    {
		if(is_string($descr)  && !empty($descr))
			$this->description .= (" ".$descr);          
    }// }}}

    /** {{{ addMeta
    * базовая функция добавления <meta> в заголовок документа.
    * В массиве аттрибутов могут быть следующие поля:
    * http-equiv, name, content, scheme.
    * Все остальные игнорируются.
    * @param    array $attr    
    * @return   boolean
    */
    function addMeta($attr)
    {
		if(!isset($attr) || !is_array($attr)) return;
		$ar = array();
        if (isset ($attr['http-equiv'])) $ar['http-equiv'] = $attr['http-equiv'];
        if (isset ($attr['name'])) $ar['name'] = $attr['name'];
        if (isset ($attr['content'])) $ar['content'] = $attr['content'];
        if (isset ($attr['scheme'])) $ar['scheme'] = $attr['scheme'];
		if(!empty($ar))
			$this->meta[] = $ar;
    }// }}}
    /** {{{ noindex
    * 
    * @param    void
    * @return   void
    */
    function noindex()
    {
        $this->robots_noindex = true;
    }// }}}

    /** {{{ nofollow
    * 
    * @param    void
    * @return   void
    */
    function nofollow()
    {
        $this->robots_nofollow = true;
    }// }}}

    /** {{{ addLink
    * базовая функция добавления <link> в заголовок документа.
    * В массиве аттрибутов могут быть следующие поля:
    * charset, href, hreflang, type, rel, rev,  title.
    * Все остальные игнорируются.
    * @return   void
    */
    function addLink( $attr )
    {
	    $ar = array();
        if (isset ($attr['charset'])) $ar['charset'] = $attr['charset'];
        if (isset ($attr['href'])) $ar['href'] = $attr['href'];
        if (isset ($attr['hreflang'])) $ar['hreflang'] = $attr['hreflang'];
        if (isset ($attr['type'])) $ar['type'] = $attr['type'];
        if (isset ($attr['rel'])) $ar['rel'] = $attr['rel'];
        if (isset ($attr['rev'])) $ar['rev'] = $attr['rev'];
		if (isset ($attr['title'])) $ar['title'] = $attr['title'];
		if (isset ($attr['cond'])) $ar['cond'] = $attr['cond'];
		if (isset ($attr['media'])) $ar['media'] = $attr['media'];

		if(!empty($ar))
			$this->links[] = $ar;
    }// }}}

    /** {{{ addCSS
    * Добавляет ссылку на css
    * @param    string $src    
    * @return   void
    */
    function addCSS($src,$cond = null,$media)
	{
		static $ccss = array();
		if(empty($src) || !is_string($src)) return;
		if(isset($ccss[$src])) return;
		$ccss[$src] = 1;
		$this->addLink(array(
			'href' => $src,
			'type' => 'text/css',
			'rel'  => 'stylesheet',
            'cond' => $cond,
            'media' => $media
		));
    }// }}}

    /** {{{ addScript
    * Добавляет тэг  <script> в заголовок. 
    * @param    string $code    Содержимое тега <script> или null, если в $attr указан src
    * @param    array $attr    Аттрибуты. Допустимые значения: type, src, language
    * @return   boolean
    */
    function addScript($src,$cond = null)
    {
        static $csrc = array();
		if(empty($src) || !is_string($src)) return;
		if(isset($csrc[$src])) return;
		$csrc[$src] = 1;
		$this->scripts[] = array(
			'src' => $src,
			'cond' => $cond
		);

    }// }}}

    /** {{{ makeMetaRobots
    * ФОрмирует мету для content='robots'
    *
    */
    protected function makeMetaRobots(){
        if (!$this->robots_noindex && !$this->robots_nofollow) return;
		$a = array();
		if ($this->robots_noindex) $a[]= 'noindex';
		if ($this->robots_nofollow) $a[] = 'nofollow';
		$this->addMeta(array(
			'name' => 'robots',
			'content' => implode(', ',$a)));
    }// }}}
    
    /** {{{ makeScript
    * Из массива аттрибутов формирует tag <script>
    */
	protected function makeScript()
	{
		$res = "";
		foreach($this->scripts as $v)
		{
			if(isset($v['cond']))
				$res .= "<!--[if ".$v['cond']."]>\n";
			$res .= "<script src=\"".$v['src']."\" type=\"text/javascript\"></script>\n";
			if(isset($v['cond']))
				$res .= "<![endif]-->\n";
		}
		return $res;
	} // }}}
    /** {{{ makeMeta
    * Из массива аттрибутов формирует tag <script>
    */
	protected function makeMeta()
	{
		$res = "";
		foreach($this->meta as $v)
		{
			$res .= "<meta ";
			foreach($v as $k2 => $v2) $res .= " $k2=\"$v2\"";
			$res .= "/>\n";
		}
		return $res;
	} // }}}
    /** {{{ makeLink
    * Из массива аттрибутов формирует tag <link>
    */
	protected function makeLink()
	{
		$res = "";
		foreach($this->links as $v)
		{
			$f = null;
			if(isset($v['cond']))
			{ 
				$f = $v['cond'] ; unset($v['cond']);
			}
			if($f)
				$res .= "<!--[if ".$f."]>\n";
			$res .= "<link ";
			foreach($v as $k2 => $v2) $res .= (isset($v2))?" $k2=\"$v2\"":"";
			$res .= "/>\n";
			if($f)
				$res .= "<![endif]-->\n";
		}
		return $res;
	} // }}}

    /** {{{ send
    * Функция отсылает заколовки клиенту.
    * 
    * Возвращает true если заголовки успешно отосланны
    * @return   boolean
    */
    function send()
    {
		/*header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT') ;
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: no-cache');*/

		$r = '';
        // DOCTYPE
		$r .= $this->predefine_doctypes[$this->doctype]."\n";
        $r.= "<html><head>\n";        

        // title
        $t = $this->getTitle();
        if ( !empty($t))
			$r .= "<title>$t</title>\n";
		
		// description
		if (isset($this->description))
			$this->addMeta(array(
                'name' => 'description',
                'content' => $this->description
                ));

		// Http-equiv!
        // content-type;
		$this->addMeta(array(
            'http-equiv'    => 'Content-Type',
            'content'       => 'text/html; charset=UTF-8'
		));
		$this->addMeta(array(
            'http-equiv'    => 'Content-Style-Type',
            'content'       => 'text/css'
        ));
		$this->addMeta(array(
            'http-equiv'    => 'Content-Script-Type',
            'content'       => 'text/javascript'
        ));

        // meta
		$this->makeMetaRobots();
		$r .= $this->makeMeta();
		$r .= $this->makeLink();
		$r .= $this->makeScript();
        $r.= "</head>\n";        

        return $r;
    }// }}}
}   
// }}}
