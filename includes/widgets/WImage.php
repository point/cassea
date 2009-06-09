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


//
// $Id$
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
        * @var      File 
        */
		$file =  null,
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
		$use_cache = 1,
        /**
		* @var      string
		*/
		$subst_src = null

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
		if(isset($elem['alt']))
			$this->setAlt((string)$elem['alt']);
		if(isset($elem['height']))
			$this->setHeight((string)$elem['height']);
		if(isset($elem['width']))
			$this->setWidth((string)$elem['width']);
		if(isset($elem['src']))
			$this->setSrc((string)$elem['src']);       	
		if(isset($elem['max_width']))
			$this->setMaxWidth((string)$elem['max_width']);
		if(isset($elem['max_height']))
			$this->setMaxHeight((string)$elem['max_height']);
		if(isset($elem['with_preview']))
			$this->setWithPreview((string)$elem['with_preview']);
		if(isset($elem['subst_src']))
			$this->setSubstSrc((string)$elem['subst_src']);

		$this->addToMemento(array("alt","height","width","src","align","max_width","max_height","with_preview","use_cache","subst_src"));

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

    // {{{ setFile
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $width    
    * @return   void
    */
    function setFile($file)
    {
        if(!isset($file) || !is_object($file) || !($file instanceof iFile)) return;
        $this->file = $file;
        $this->setSrc( ($this->file->exists())?$this->file->getURL():null ) ;
    }
    // }}}
    
    // {{{ getFile
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getFile()
    {
		return $this->file;
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
		if(substr($src,0,4) != "http" && strpos($src,"/") !== 0)
            $src = "/".$src;
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
        $this->checkAndSetData();

		if(!$this->file instanceof iFile && isset($this->src) && isset($this->subst_src))
				$this->setSrc(sprintf($this->getSubstSrc(),$this->getSrc()));
		
		if(empty($this->alt))
			$this->setAlt(basename($this->getSrc()));
		if(empty($this->src)) 
		{
            $this->setVisible(0);
       		parent::preRender();
            return;
        }

		if(($this->getMaxWidth() || $this->getMaxHeight()) && !is_null($this->file))
        {

            list($width, $height) = getimagesize($this->file->getPath());

            $a = recalcSize($width, $height,$this->getMaxWidth(),$this->getMaxHeight());
            $this->setWidth($a['0']);
			$this->setHeight($a['1']);
		}

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
    function setData(WidgetResultSet $data)
    {
		$this->setFile($data->getDef());
        $this->setFile($data->get('file'));
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
			"alt"=> Language::encodePair($this->getAlt()),
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
	
    // {{{ setSubstSrc
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $subst_src
    * @return   void
    */
    function setSubstSrc($subst_src)
    {
		if(!isset($subst_src) || !is_scalar($subst_src)) 
			return ;
		$this->subst_src = (string)$subst_src;
    }
    // }}}
    
    // {{{ getSubstSrc
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getSubstSrc()
    {
		return $this->subst_src;
    }
    // }}}
}
//}}}

?>
