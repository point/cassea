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

//{{{ DataSourceObject
class DataSourceObject extends DataObject
{
	protected
		/**
		* @var  string
		*/
		$datasource_methods = array(),
		/**
		* @var  array
		*/
		$datasource_params = array()
	;
	private
		$cache = null
		;
	function __construct($static = false)
	{
		parent::__construct($static);
	}
	function parseParams(SimpleXMLElement $elem)
	{
		parent::parseParams($elem);
        foreach($elem->datasource as $ds)
        {
            if(isset($ds['method']))
                $this->datasource_methods[] = (string)$ds['method'];
            else
                throw new DataObjectException("Data source method was not found");

            $this->datasource_params[] = new DataObjectParams($ds);		
        }
	}
	function hasDatasourceParamFrom($from)
	{
        if(empty($this->datasource_params)) return false;
        foreach($this->datasource_params as $dsp)
            foreach($dsp->getParamsFrom() as $v)
                if($v == $from) return true;
		return false;
	}
	function getData($w_id = null)
	{
		if(!$this->is_static)
		{
			if(!isset($this->object) && !$this->createObject()) return null;
            if(!empty($this->datasource_methods))
            {
				$ret = array();
                foreach($this->datasource_methods as $ind => $method)
				{
					$dsp = $this->datasource_params[$ind];
                    $dsp->replaceLimitParams();
					try{
						$r = new ReflectionObject($this->object);
						$ret[] = $r->getMethod($method)->invokeArgs($this->object,$dsp->getParams());
					}catch(ReflectionException $e){}
                }
                return $ret;
            }
            else
            if($w_id !== null && ($v = $this->findValueInObject($w_id)) !== false)
                return $v;
		}
		else
        {
            if(!empty($this->datasource_methods))
            {
                $this->requireClasses();

				$ret = array();
                foreach($this->datasource_methods as $ind => $method)
                {
					$dsp = $this->datasource_params[$ind];
					$dsp->replaceLimitParams();
                    try
                    {
                        $r = new ReflectionClass($this->classname);
                        if(!$r->getMethod($method)->isAbstract() && $r->getMethod($method)->isStatic())
                            $ret[] = call_user_func_array($this->classname."::".$method,$dsp->getParams());
                    }
                    catch(ReflectionException $e){return null;}

                }
                return $ret;
            }
            elseif(($v = $this->findValueInStatic($w_id)) !== false)
                return $v;
		}
		return null;
	}
	
    function findValueInObject($w_id)
    {
		if(!isset($w_id) || ($w_id[0] === "_" && $w_id[1] === "_") || !isset($this->object)) return false;
        try
        {
            $w_id = strtolower($w_id);
            $ro = new ReflectionObject($this->object);
            if($ro->hasProperty($w_id) && @t($p = $ro->getProperty($w_id))->isPublic())
                return $p->getValue($this->object);
            if($ro->hasMethod("get".$w_id) && @t($m = $ro->getMethod("get".$w_id))->isPublic())
                return $m->invoke($this->object);
            if($ro->hasMethod("get".str_replace("_","",$w_id)) && @t($m = $ro->getMethod("get".str_replace("_","",$w_id)))->isPublic())
                return $m->invoke($this->object);
            if($ro->hasMethod("get_".$w_id) && @t($m = $ro->getMethod("get_".$w_id))->isPublic())
                return $m->invoke($this->object);
            if($ro->hasMethod($w_id) && @t($m = $ro->getMethod($w_id))->isPublic())
                return $m->invoke($this->object);
            if($ro->hasMethod("__get"))
                return $ro->getMethod("__get")->invoke($this->object,$w_id);
            if($ro->hasMethod("__call"))
                return $ro->getMethod("__call")->invoke($this->object,$w_id);

            
        }
        catch(ReflectionException $e){ return false;}

        return false;
    }

    function findValueInStatic($w_id)
    {
		if(!isset($w_id) || ($w_id[0] === "_" && $w_id[1] === "_") || !isset($this->object)) return false;
        try
        {
            $w_id = strtolower($w_id);
            $ro = new ReflectionClass($this->classname);
            if($ro->hasProperty($w_id) && @t($p = $ro->getProperty($w_id))->isPublic() && $p->isStatic())
                return $p->getValue(null);
            if($ro->hasMethod("get".$w_id) && @t($m = $ro->getMethod("get".$w_id))->isPublic() && $m->isStatic())
                return $m->invoke(null);
            if($ro->hasMethod("get".str_replace("_","",$w_id)) && @t($m = $ro->getMethod("get".str_replace("_","",$w_id)))->isStatic())
                return $m->invoke($this->object);
            if($ro->hasMethod("get_".$w_id) && @t($m = $ro->getMethod("get_".$w_id))->isPublic() && $m->isStatic())
                return $m->invoke(null);
            if($ro->hasMethod($w_id) && @t($m = $ro->getMethod($w_id))->isPublic() && $m->isStatic())
                return $m->invoke(null);
        }
        catch(ReflectionException $e){ return false;}

        return false;
    }

	function hasDatasourceMethod()
	{
		return count($this->datasource_methods);
	}
}
// }}}
