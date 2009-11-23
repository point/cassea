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
// $Id: WRuler.php 172 2009-10-27 11:57:52Z point $
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
        $current_page = 1,
        /**
        * @var      int
        */
        $links_per_page = 9,
        /**
        * @var      boolean
        */
		$show_prev = false,
        /**
        * @var      boolean
        */
		$show_next = false,
        /**
        * @var      boolean
		*/
		$another_ruler = false,
        /**
        * @var      boolean
		*/
		$use_get = false,

        $total_pages = null,

        $begin = null,

		$end = null,
        /**
        * @var      int
		*/
		$max_res_per_page = 10

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
		if(isset($elem['max_res_per_page']))
			$this->setMaxResPerPage((string)$elem['max_res_per_page']);

		if(($c_rpp = Controller::getInstance()->get->{$this->getId()."_rpp"}) !== null)
			$this->setResPerPage($c_rpp,true);
		elseif(isset($elem['res_per_page']))
	       	$this->setResPerPage((string)$elem['res_per_page']);
		if(isset($elem['links_per_page']))
			$this->setLinksPerPage((string)$elem['links_per_page']);
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

		$controller = Controller::getInstance();
		$controller->getDispatcher()->addSubscriber("ruler_settotal",$this->getId());
		$controller->getDispatcher()->addSubscriber("ruler_has_another_instance",$this->getId());
		$controller->getDispatcher()->addEvent("roll_setlimits");
		$controller->getDispatcher()->addEvent("ruler_has_another_instance");

		$this->calcCurrentPage();
		$controller->getDisplayModeParams()->predicted_from = $this->getResPerPage()*($this->current_page-1);
		$controller->getDisplayModeParams()->predicted_limit = $this->getResPerPage();
		parent::buildComplete();
	}    
	// }}}

	// {{{ messageInterchange
	/**
    * method description
    *
    * more detailed method description
    * @param    void
    * @return   void
    */
	function messageInterchange()
	{
		Controller::getInstance()->getDispatcher()->notify(
			new WidgetEvent("ruler_has_another_instance",$this->getId(),null));
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
		if($this->total_pages == 1)
			$this->setVisible(0);
		$items = array();
			for($i = $this->begin; $i <= $this->end; $i++)
				//for($i = $this->end; $i >= $this->begin; $i--)
				$items[$i] = array(
					"title"=>$i,
					"class"=>$i == $this->current_page?"class=\"current\"":"",
					"link"=>$this->makeLink($i)
				);
			$this->tpl->setParamsArray(array("items"=>$items,"show_prev"=>0+$this->show_prev,"show_next"=>0+$this->show_next,
				"first_link"=>$this->makeLink($this->current_page-1),"last_link"=>$this->makeLink($this->current_page+1)));
		parent::assignVars();
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
    function setResPerPage($res_per_page,$limit_to_max_res_per_page = false)
    {
		if(!isset($res_per_page) || $res_per_page < 1)
			return;
		if($limit_to_max_res_per_page)
			$this->res_per_page = min($this->getMaxResPerPage(), abs(0 + $res_per_page));
		else
			$this->res_per_page = abs(0 + $res_per_page);
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
	
    // {{{ setMaxResPerPage 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $res_per_page    
    * @return   void
    */
    function setMaxResPerPage($max_res_per_page)
    {
		if(!isset($max_res_per_page) || $max_res_per_page < 1)
			return;
		$this->max_res_per_page = abs(0+$max_res_per_page);
    }
    // }}}
    // {{{ getMaxResPerPage 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getMaxResPerPage()
    {
		return $this->max_res_per_page;
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

    function handleEvent(WidgetEvent $event)
    {
		if($event->getName() == "ruler_settotal")
		{
			$this->setTotalCount($event->getParam('total_count'));
			$this->calc();

			Controller::getInstance()->getDispatcher()->notify(
				new WidgetEvent("roll_setlimits",$this->getId(),$event->getSrc(),array('from'=>($this->current_page-1)*$this->res_per_page,
				"limit"=>(($_r = $this->total_count - $this->current_page*$this->res_per_page) < 0)?$this->res_per_page+$_r:$this->res_per_page
			
			)));
		}
		elseif($event->getName() == "ruler_has_another_instance")
		{
			$this->setUseGET(1);
			$controller = Controller::getInstance();
			$controller->getDisplayModeParams()->predicted_from = $controller->getDisplayModeParams()->prdicted_limit = null;
		}
		parent::handleEvent($event);
	}
	
	protected function calc()
	{
		if($this->total_count <= $this->res_per_page)
		{ 			$this->begin = $this->end = $this->total_pages = 1;	return; }
		if(($this->current_page-1)  * $this->res_per_page > $this->total_count)
		{ /*$this->current_page = 1;*/ $this->begin = 1; $this->end = $this->links_per_page; return; }

		$this->total_pages = $total_pages = ceil($this->total_count / $this->res_per_page);

		$this->begin = (($_b = $this->current_page - ceil($this->links_per_page/2)) <= 0)?1:($_b);
		$this->end = (($_e = $this->begin + ($this->links_per_page-1)) > $total_pages)?$total_pages:$_e;

		if($this->current_page > 1)
		$this->show_prev = 1;

		if($this->current_page < $this->total_pages)
			$this->show_next = 1;
	}
	protected function makeLink($page)
	{
		if($page <= 1) $page = null;
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
		$this->current_page = 1;
		if(!$this->getUseGET())
		{
			foreach(Controller::getInstance()->p2 as $v)
				if(preg_match("/^page(\d+)$/",$v,$m) && is_numeric($m[1]) && $m[1] > 0)
					$this->current_page = Filter::apply(0+$m[1],Filter::INT);
		}
		else
		{
			$page = Controller::getInstance()->get->{"page".$this->id};
			if($page !== null && is_numeric($page) && $page > 0)
				$this->current_page = Filter::apply(0+$page,Filter::INT);
		}
	}
}
//}}}

?>
