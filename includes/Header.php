<?php
/*- vim:expandtab:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008, Cassea Project
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
            'strict'        => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
            'transitional'  => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
            'frameset'      => '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">'
            );
        /**
        * Заголовок документа
        * @var      string
        */
        private $title = null;
        
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
    * @param    string $url    Адрес пересылки
    * @return   void
    */
    function redirect( $url)
    {
		header('Location: '.$url);
        die();
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

    /** {{{ addToTitle
    * Функция добавляет в заголовок заданную строку.
    * 
    * Функция необходима для создания заголовка страницы по типу:
    * :: главная :: каталог :: мобильные телефоны ::
    * @param    string $title   
    * @return   void
    */
    function addToTitle($title)
	{
		if(is_string($title)  && !empty($title))
			$this->title .= (" ".$str);
    }// }}}

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
        if ( isset($this->title))
			$r .= "<title>$this->title</title>\n";
		
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
    /** {{{ footer
    * Возвращает закрывающий </html> и генерирует событие OutputEnd
    * @return   string
    */
    /*function footer()
    {
        echo "</html>";
        $d = DB::get();
        $d->sql_close();
          
	}*/// }}}
}   
// }}}
?>
