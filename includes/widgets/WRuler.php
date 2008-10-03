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


//
// $Id:$
//
//{{{ WRuler 
class WRuler extends WContainer
{
    protected 

        /**
        * @var      int
        */
        $res_per_page = 10,
        /**
        * @var      int
        */
        $total_count = PHP_INT_MAX,
        /**
        * @var      int
        */
        $current_page = null,
        /**
        * @var      int
        */
        $links_per_page = 9,
        /**
        * @var      boolean
        */
		$show_first = false,
        /**
        * @var      boolean
        */
		$show_last = false,
        /**
        * @var      boolean
		*/
		$another_ruler = false,
        /**
        * @var      boolean
		*/
		$reverse = false,
        /**
        * @var      boolean
		*/
		$use_get = false,

		$total_pages

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
		if(isset($elem['res_per_page']))
	       	$this->setResPerPage((string)$elem['res_per_page']);
		if(isset($elem['links_per_page']))
			$this->setLinksPerPage((string)$elem['links_per_page']);
		if(isset($elem['reverse']))
			$this->setReverse((string)$elem['reverse']);
		if(isset($elem['use_get']))
			$this->setUseGET((string)$elem['use_get']);
		
		parent::parseParams($elem);		    	
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
		/*if(isset($this->get_post_params['cp']))
			$this->current_page = 0 + $this->get_post_params['cp'];*/

		Controller::getInstance()->getDispatcher()->addSubscriber("ruler_settotal",$this->getId());
		Controller::getInstance()->getDispatcher()->addSubscriber("ruler_has_another_instance",$this->getId());
		Controller::getInstance()->getDispatcher()->addEvent("roll_setlimits");
		Controller::getInstance()->getDispatcher()->addEvent("ruler_has_another_instance");

		Controller::getInstance()->getDispatcher()->notify(
			new Event("ruler_has_another_instance",$this->getId(),null));

		if(!$this->getReverse())
		{
			$this->calcCurrentPage();
			$controller = Controller::getInstance();
			$controller->getDisplayModeParams()->predicted_from = $this->getResPerPage()*($this->current_page-1);
			$controller->getDisplayModeParams()->predicted_limit = $this->getResPerPage();
		}
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
		if(!isset($this->tpl))
			$this->tpl = $this->createTemplate();
		parent::preRender();
    }
	// }}}    

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
		$items = array();
		if(!$this->getReverse())
		{
			for($i = $this->begin; $i <= $this->end; $i++)
				//for($i = $this->end; $i >= $this->begin; $i--)
				$items[$i] = array(
					"title"=>$i,
					"class"=>$i == $this->current_page?"class=\"current\"":"",
					"link"=>$this->makeLink($i)
				);
			$this->tpl->setParamsArray(array("items"=>$items,"show_first"=>$this->show_first,"show_last"=>$this->show_last,
				"first_link"=>$this->makeLink(null),"last_link"=>$this->makeLink($this->total_pages)));
		}
		else
		{
			for($i = $this->end; $i >= $this->begin; $i--)
				$items[$i] = array(
					"title"=>$i,
					"class"=>$i == $this->current_page?"class=\"current\"":"",
					"link"=>$this->makeLink($i)
				);
			$this->tpl->setParamsArray(array("items"=>$items,"show_first"=>$this->show_first,"show_last"=>$this->show_last,
				"first_link"=>$this->makeLink(null),"last_link"=>$this->makeLink(1)));
		}
		parent::assignVars();
    }
	// }}}	
    // {{{ setReverse
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $reverse
    * @return   void
    */
    function setReverse($reverse)
    {
		if(!isset($reverse) || !is_scalar($reverse)) return;
		$this->reverse = 0+$reverse;
    }
    // }}}
    // {{{ getReverse
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getReverse()
    {
		return $this->reverse;
    }
    // }}} 
    // {{{ setResPerPage 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $res_per_page    
    * @return   void
    */
    function setResPerPage($res_per_page)
    {
		if(!isset($res_per_page) || $res_per_page < 1)
			return;
		$this->res_per_page = 0 + $res_per_page;
    }
    // }}}
    // {{{ getResPerPage 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getResPerPage()
    {
		return $this->res_per_page;
    }
    // }}}
    // {{{ setLinksPerPage
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $links_per_page
    * @return   void
    */

    function setLinksPerPage($links_per_page)
    {
		if(!isset($links_per_page) || $links_per_page < 1)
			return;
		$this->links_per_page = 0 + $links_per_page;
    }
    // }}}
    // {{{ getLinksPerPage 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getLinksPerPage()
    {
		return $this->links_per_page;
    }
    // }}}
    // {{{ setTotalCount 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $count    
    * @return   void
    */
    function setTotalCount($count)
    {
		if(!isset($count) || $count < 0) return;
		$this->total_count = 0 + $count;
    }
    // }}}
    // {{{ getTotalCount 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getTotalCount()
    {
		return $this->total_count;
    }
    // }}}
    // {{{ setUseGET
    /**
    * Method description
    *
    * More detailed method description
    * @param    bool $use
    * @return   void
    */
    function setUseGET($use)
    {
		if(!isset($use) || !is_scalar($use)) return;
		$this->use_get = 0+$use;
    }
    // }}}
    // {{{ getUseGET
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
	function getUseGET()
	{
		return $this->use_get;
    }
    // }}}

    function handleEvent($event)
    {
		if($event->getName() == "ruler_settotal")
		{
			$this->setTotalCount($event->getParam('total_count'));
			$this->calcCurrentPage();
			$this->calc();
			if(!$this->getReverse())
				$cur_limit = $this->res_per_page*($this->current_page-1);
			else $cur_limit = $this->res_per_page*($this->total_pages - $this->current_page);

			if($cur_limit >= $this->total_count)
				$cur_limit = $this->res_per_page*($this->current_page-1);

			$cur_count = $this->res_per_page;
			if($cur_limit + $cur_count > $this->total_count)
				$cur_count = $this->total_count - $cur_limit ;

			Controller::getInstance()->getDispatcher()->notify(
				new Event("roll_setlimits",$this->getId(),$event->getSrc(),array('from'=>$cur_limit,
				"limit"=>$cur_count)));
		}
		if($event->getName() == "ruler_has_another_instance")
		{
			$this->setUseGET(1);
			$controller = Controller::getInstance();
			$controller->getDisplayModeParams()->predicted_from = $controller->getDisplayModeParams()->prdicted_limit = null;
		}
	}
	
	protected function calc()
	{
		$this->total_pages = ceil($this->total_count/$this->res_per_page);
		if($this->total_pages <= 0) $this->total_pages = 1;

		if($this->getReverse() && !isset($this->current_page))
			$this->current_page = $this->total_pages;

		if($this->current_page <= 0 || $this->current_page > $this->total_pages)
			if(!$this->getReverse())
				$this->current_page = 1;
			else $this->current_page = $this->total_pages;

		if($this->current_page <= 0 + floor($this->links_per_page/2))
		{
			$this->begin = 1;
			if($this->begin + $this->links_per_page <= $this->total_pages)
				$this->end = $this->links_per_page;
			else $this->end = $this->total_pages;
		}
		elseif($this->current_page > $this->total_pages - floor($this->links_per_page/2))
		{
			$this->end = $this->total_pages;
			if($this->end - ($this->links_per_page-1) >= 0)
				$this->begin = $this->end - ($this->links_per_page - 1);
			else $this->begin = 1;
		}
		else
		{
			$this->begin = $this->current_page - floor($this->links_per_page/2);
			$this->end = $this->begin + $this->links_per_page-1;
		}
		if($this->total_pages == 1)
			$this->setVisible(0);

		if($this->current_page > 1)
			if(!$this->getReverse())
				$this->show_first = 1;
			else $this->show_last = 1;

		if($this->current_page < $this->total_pages)
			if(!$this->getReverse())
				$this->show_last = 1;
			else $this->show_first = 1;
	}
	protected function makeLink($page)
	{
		$link = "#";
		if(!$this->getUseGET())
		{
			if($page === null)
				$ret = Controller::getInstance()->makeURL(null,array("/^page\d+$/"=>null));
			else $ret = Controller::getInstance()->makeURL(null,array("/^page\d+$/"=>"page".$page));
			return $ret;
		}
		else
		{
			if($page === null)
				$ret = Controller::getInstance()->makeURL(null,null,null,array("page".$this->id=>null));
			else $ret = Controller::getInstance()->makeURL(null,null,null,array("page".$this->id=>$page));
			return $ret;
		}
	}
	protected function calcCurrentPage()
	{
		if(!$this->getReverse())
			$this->current_page = 1;
		if(!$this->getUseGET())
		{
			foreach(Controller::getInstance()->p2 as $v)
				if(preg_match("/^page(\d+)$/",$v,$m) && is_numeric($m[1]))
					$this->current_page = Filter::filter(0+$m[1],Filter::INT);
		}
		else
		{
			$page = Controller::getInstance()->get->{"page".$this->id};
			if($page !== null && is_numeric($page))
				$this->current_page = Filter::filter(0+$page,Filter::INT);
		}
	}
}
//}}}

?>
