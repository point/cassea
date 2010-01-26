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

//{{{ WObject
abstract class WObject
{
 
 	private $log;   
	private static $s_counter = 0;
	/**
    * @var      string
    */
	protected $id = null;

	// {{{ __construct
    function __construct($id = null)
    {
    	$this->log = null; //&WLog::getInstance();
		$this->setID($id);
        //$this->setClassNameToLower(strtolower(get_class($this)));
    }
	// }}}
	
	// {{{ getID 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getID()
    {
		return $this->id;
    }
    // }}}
    
    // {{{ setID 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $id    
    * @return   void
    */
    function setID($id = null)
	{
		if(empty($id) || is_numeric(substr($id,0,1)))
			$id = "__s".(self::$s_counter++);
        else
            $id = (string)$id;

		if(strpos($id,'[') !== false || strpos($id,']') !== false)
			$id = str_replace('[','_',
				str_replace(']','_',$id));
		$this->id = $id;
    }
    // }}}
	// {{{ getProperties
	function getProperties()
	{
		$class = get_class($this);
		$ret_prop = array();
		foreach($this as $k=>$v)
		{
			try{
			$prop = new ReflectionProperty($class, $k);
			if($prop->isPublic() || $prop->isProtected())
				$ret_prop[] = $k;
			}catch(Exception $e){}
		}
		return $ret_prop;
	}
	// }}}

	// {{{ __clone
	/*function __clone(){
		foreach($this as $name => $value){
			//if(gettype($value)=='object'){
			if(is_object($value)){
				if($value instanceof WComponent && !$value->getObjectChanged()) continue;
				$this->$name= clone($this->$name);
			}
		}
		}*/
	// }}}
}
//}}}

?>
