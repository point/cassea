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

// $Id:$

//{{{ DataObjectParams
class DataObjectParams
{
	protected 
		/**
		 * @var  array
		 */
		$params = array(),
		$params_from = array()
		;
	function __construct(SimpleXMLElement $elem = null)
	{
		if(!isset($elem,$elem->param)) return;

		$controller = Controller::getInstance();
		$p2_cursor = 0;
		foreach($elem->param as $param)
		{
			if($param['from'] == "p1")
			{
				$this->params_from[] = "p1";
				//$p = (isset($param['as']) && $param['as'] == "array")?array($controller->p1):$controller->p1;
				$p = $controller->p1;
				if(isset($param->filter))
					$p = Filter::filter($p,(string)$param->filter);
				if((isset($param['as']) && $param['as'] == "array"))
					$this->params[] = array($p);
				else $this->params[] = $p;
			}
			elseif($param['from'] == "p2")
			{
				$this->params_from[] = "p2";

				$c = count($controller->p2);
				if(isset($param['count']))
					$c = abs(0+$param['count']);

				$p = array();
				for($i = 0; $i < $c;$i++,$p2_cursor++)
				{
					//if(!isset($controller->p2[$i])) continue;
					$p[$i] = isset($controller->p2[$p2_cursor])?$controller->p2[$p2_cursor]:null;
					if(isset($param->filter[$i]))
						$p[$i] = Filter::filter($p[$i],(string)$param->filter[$i]);
				}
				if(isset($param['as']) && $param['as'] == "array")
					$this->params[] = array_filter($p);
				else
					foreach($p as $_p)
						$this->params[] = $_p;
			}
			elseif($param['from'] == "p3" && isset($param['var']))
			{
				$this->params_from[] = "p3";

				/*$p = (isset($param['as']) && $param['as'] == "array")?array($controller->get->$param['var']):
					$controller->get->$param['var'];*/
				$p = $controller->get->$param['var'];
				if(isset($param->filter))
					$p = Filter::filter($p,(string)$param->filter);
				if(isset($param['as']) && $param['as'] == "array")
					$this->params[] = array($p);
				else $this->params[] = $p;
			}
			elseif(isset($param['constant']))
			{
				$this->params_from[] = "constant";

				$p = (string)$param['constant'];
				if(isset($param->filter))
					$p = Filter::filter($p,(string)$param->filter);
				$this->params[] = $p;

			}
			elseif($param['from'] == "limit")
			{
				$this->params_from[] = "limit";
				$this->params[] = array();
			}
		
		}
	}
	function getParams()
	{
		return $this->params;
	}
	function getParamsFrom()
	{
		return $this->params_from;
	}
	function replaceLimitParams()
	{
		$controller = Controller::getInstance();
		foreach($this->params_from as $k=>$v)
			if($v == "limit")
				$this->params[$k] = array(	'from'=>$controller->getDisplayModeParams()->predicted_from,
					'limit'=>$controller->getDisplayModeParams()->predicted_limit);
	}
}
//}}}
