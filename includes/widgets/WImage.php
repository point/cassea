<?php
//
// $Id:$
//
WidgetLoader::load("WComponent");
//{{{ WImage
class WImage extends WComponent
{
    protected

        /**
        * @var      string
        */
        $alt = null,
        /**
        * @var      string
        */
        $height = null,
        /**
        * @var      string
        */
        $max_height = null,
        /**
        * @var      string
        */
        $width = null,
        /**
        * @var      string
        */
        $max_width = null,
        /**
        * @var      string
        */
		$src = "",
        /**
        * @var      string
        */
		$align = null,
        /**
        * @var      boolean
        */
		$with_preview = 0,
		 /**
        * @var      boolean
        */
		$use_cache = 1

		;
    
    // {{{ __construct
    /**
    * Method description
    *
    * More detailed method description
    * @param    array $params
    */
    function __construct($id = null)
    {
		parent::__construct($id);
    }
    // }}}
    // {{{ parseParams
    /**
    * Method description
    *
    * More detailed method description
    * @param    array
    * @return void
    */
    function parseParams(SimpleXMLElement $elem)
    {
		$this->setAlt((string)$elem['alt']);
	    $this->setHeight((string)$elem['height']);
		$this->setWidth((string)$elem['width']);
		$this->setSrc((string)$elem['src']);       	
		$this->setMaxWidth((string)$elem['max_width']);
		$this->setMaxHeight((string)$elem['max_height']);
		$this->setWithPreview((string)$elem['with_preview']);

		$this->addToMemento(array("alt","height","width","src","align","max_width","max_height","with_preview","use_cache"));

		parent::parseParams($elem);		    	
    }
    // }}}
    // {{{ setAlt 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $alt    
    * @return   void
    */
    function setAlt($alt)
    {
		if(!isset($alt) || !is_scalar($alt)) 
			return ;
		$this->alt = "".$alt;
    }
    // }}}
    
    // {{{ getAlt 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getAlt()
    {
		return $this->alt;
    }
    // }}}
    
    // {{{ setHeight 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $height    
    * @return   void
    */
    function setHeight($height)
    {
		if(!isset($height) || !is_scalar($height)) 
			return ;
		$this->height = 0 + $height;
    }
    // }}}
    
    // {{{ getHeight 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getHeight()
    {
		return $this->height;
    }
    // }}}
    
    // {{{ setWidth 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $width    
    * @return   void
    */
    function setWidth($width)
    {
		if(!isset($width) || !is_scalar($width)) 
			return ;
		$this->width = 0 + $width;
    }
    // }}}
    
    // {{{ getWidth 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getWidth()
    {
		return $this->width;
    }
    // }}}
    // {{{ setSrc 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $src    
    * @return   void
    */
    function setSrc($src)
    {
		if(!isset($src) || !is_scalar($src)) 
			return ;
		$this->src = $src;
    }
    // }}}
    
    // {{{ getSrc 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getSrc()
    {
		return $this->src;
    }
    // }}}
    // {{{ buildComplete
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   void
    */
	function buildComplete()
	{
		if(!isset($this->tpl))
			$this->tpl = $this->createTemplate();
		parent::buildComplete();
	}    
	// }}}
    // {{{ preRender
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   void
    */
    function preRender()
    {
		if(isset($this->dataset))
			$this->setData($this->dataset->getData($this->getId()));
		
		//!!! Beware
		if(empty($this->src)) {$this->setVisible(0);return;}

		$a = recalcSizeArray(getImgSize($this->getSrc()),$this->getMaxWidth(),$this->getMaxHeight());
		$this->setWidth($a['width']);
		$this->setHeight($a['height']);

		if($this->getWithPreview())
		{
			$js = <<<EOD
$(document).ready(function(){
	$('#{$this->getId()}').css('cursor','pointer').click(function(){
		var iot = $("#{$this->getId()}").offset().top;
		$('<div id="{$this->getId()}_preview" class="image_preview"><img src="{$this->getSrc()}" /></div>').appendTo('body');
		var i_w = $('#{$this->getId()}_preview > img').width();
		var i_h = $('#{$this->getId()}_preview > img').height();
		var p = p1 = p2= 1;
		if(i_w > 800) p1 = 800 / i_w;
		if(i_h > 600) p2 = 600 / i_h;
		p = Math.min(p1,p2);
		i_w = i_w*p;i_h = i_h*p;
		var o_l = $("#{$this->getId()}").offset().left;
		if(o_l < 20) o_l = 20;
		if(o_l + i_w > $(window).width())
			o_l = $(window).width()-i_w-40;
		$('#{$this->getId()}_preview').width(i_w).height(i_h).css('left',o_l).css('top',iot);
		$('#{$this->getId()}_preview > img').width(i_w).height(i_h).click(function() {
			$("#{$this->getId()}_preview").remove();
		});
	});
});
EOD;
			$this->javascript->addBeforeWidget($js);
		}


		parent::preRender();
    }

	// }}}    
    // {{{ setData 
    /**
    * Method description
    *
    * More detailed method description
    * @param    mixed $data
    * @return   void
    */
    function setData(ResultSet $data)
	{
		if($this->getId() != $data->getFor()) return;

		$this->restoreMemento();
		$this->setSrc($data->getDef());
		$this->setSrc($data->get('src'));
		$this->setWidth($data->get('width'));
		$this->setHeight($data->get('height'));
		$this->setAlt($data->get('alt'));
		$this->setMaxWidth($data->get('max_width'));
		$this->setMaxHeight($data->get('max_height'));
		$this->setWithPreview($data->get('with_preview'));

		parent::setData($data);
    }
    //}}}
    // {{{ assignVars
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   void
    */
    function assignVars()
    {
		$this->tpl->setParamsArray(array(
			"alt"=> ($this->getAlt())?'alt="'.$this->getAlt().'"':'',
			"width" => ($this->getWidth())?' width="'.$this->getWidth().'"':'',
			"height" => ($this->getHeight())?' height="'.$this->getHeight().'"':'',
			"src" => $this->getSrc(),
			'preview' => $this->getWithPreview()
		));

		parent::assignVars();
    }
	// }}}	

    // {{{ setMaxHeight 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $height    
    * @return   void
    */
    function setMaxHeight($max_height)
    {
		if(!isset($max_height) || !is_scalar($max_height)) 
			return ;
		if(!empty($this->height)) return;

		$this->max_height = 0 + $max_height;
    }
    // }}}
    
    // {{{ getMaxHeight 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getMaxHeight()
    {
		return $this->max_height;
    }
    // }}}
    // {{{ setMaxWidth 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $width    
    * @return   void
    */
    function setMaxWidth($max_width)
    {
		if(!isset($max_width) || !is_scalar($max_width)) 
			return ;
		if(!empty($this->width)) return;
		$this->max_width = 0 + $max_width;
    }
    // }}}
    
    // {{{ getMaxWidth 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getMaxWidth()
    {
		return $this->max_width;
    }
    // }}}
    // {{{ setWithPreview
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $width    
    * @return   void
    */
    function setWithPreview($with_preview)
    {
		if(!isset($with_preview) || !is_scalar($with_preview)) 
			return ;
		$this->with_preview = 0 + $with_preview;
    }
    // }}}
    
    // {{{ getWithPreview
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getWithPreview()
    {
		return $this->with_preview;
    }
    // }}}
}
//}}}

?>
