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

//{{{ DataUpdaterPool
class DataUpdaterPool
{
	static $pool = array();

	static function set(DataHandlerObject $dho, $priority = 0,$id = null,$form_ids = null)
	{
		self::$pool[] = array('priority'=>$priority,
            'data_handler_object'=>$dho,'id'=>$id,
            'form_ids' => $form_ids);

		usort(self::$pool,create_function('$a,$b',
			'return ($a["priority"] < $b["priority"])?-1:1;'));
	}
    static function getById($id)
    {
        foreach(self::$pool as $o)
            if($o['id'] == $id)
                return $o['data_handler_object'];
        return null;
    }
	/*static function savePool()
	{
		$storage = Storage::createWithSession("DataUpdaterPool".Controller::getInstance()->getStoragePostfix());
		$storage->set('pool',self::$pool);
	}
	static function restorePool()
	{
		$storage = Storage::createWithSession("DataUpdaterPool".Controller::getInstance()->getStoragePostfix());
		self::$pool = $storage->get('pool');
	}*/
	static function callCheckers($form_id = null)
	{
		$controller = Controller::getInstance();
		foreach(self::lookupDHs($form_id) as $dho)
			$dho->check($controller->post);
	}
	static function callHandlers($form_id = null)
	{
		$controller = Controller::getInstance();
		foreach(self::lookupDHs($form_id) as $dho)
			$dho->handle($controller->post);

	}
	static function callFinilze($form_id = null)
	{
		$controller = Controller::getInstance();
		foreach(self::lookupDHs($form_id) as $dho)
			$dho->finalize($controller->post);
	}
	private static function lookupDHs($form_id)
	{
		$dhls = array();
		// form_id doesn't set if request passed via ajax
		// also looking up datahandlers to check, handle or finalize POSTs
		// if no special DHs founded, calling all DHs
		if(isset($form_id))
			foreach(self::$pool as $p)
				if(!empty($p['form_ids']) && in_array($form_id,$p['form_ids']) && 
					($dho = $p['data_handler_object']))
					$dhls[] = $dho;

		if(empty($dhls)) 
			foreach(self::$pool as $p)
				if(($dho = $p['data_handler_object']) && empty($p['form_ids']))
					$dhls[] = $dho;

		return $dhls;
	}
}
// }}}
