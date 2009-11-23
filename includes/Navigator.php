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

/**
 * This file contains class for storing navigation
 * history that made current user.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: Navigator.php 179 2009-10-29 13:10:19Z point $
 * @package system
 * @since 
 */

//{{{ Navigator
/**
 * Object of this class automatically created by the {@link Controller}
 * in one instance per request. If you want to take access over 
 * this object, use <code>Controller::getInstance()->getNavigator()</code>
 * method.
 *
 * Navigator is useful in pair with the WPageHandler. Specifying 
 * <code>' goto="-1" ' </code> cause to walk through the saved steps 
 * (so through the history of visited pages) and extract the URL to
 * redirect to in automatic mode.
 *
 * Steps to the particular objects added automatically by the Controller
 * during the GET request.
 *
 * This class has trying to handle back-forward jumps correctly.
 */
class Navigator
{
	private 
		/**
		 * Storage that used to store steps for the current user.
		 * @var Storage instance
		 */
		$storage = null,
		/**
		 * Array of steps. Last inserted item located at the 0 index
		 * @var array
		 */
		$user_path = array(),
		/**
		 * Current controller name
		 * @var string
		 */
		$controller_name = null
			;

	/**
	 *  Maximum count of the steps to store.
	 *  It's should be enough for most sites.
	 */
    const MAX_PATH = 20;
	
	//{{{ __construct
	/**
	 * Creating instance and initializing class properties.
	 * Trying to restore steps saved in persistent storage.
	 * @param null
	 * @return null
	 */
	function __construct()
	{
		$this->storage = Storage::createWithSession('AdminNavigator'.Controller::getInstance()->getStoragePostfix());

		$this->controller_name = Controller::getInstance()->getControllerName();
		if(empty($this->controller_name))
			$this->controller_name = "index";

		$this->user_path = $this->storage->get("user_path");

		if(empty($this->user_path) || $this->user_path === false) $this->user_path = array();
	}
	//}}}

	//{{{ addStep
	/**
	 * Adding step to the list. Usually it happens in 
	 * {@link Controller::init} method and only during the GET request and
	 * provided that Controller's register_step variable is setted to non false value.
	 * There is no need to add step if current request method is POST.
	 *
	 * Optionally title and description properties might be specified
	 * to describe the current page.
	 *
	 * If for the current user controller name has been changed, 
	 * all history will be cleaned up.
	 *
	 * If the user has been already visited page, already situated in the list,
	 * all steps, which were added after it will be deleted.
	 * (if we jumping to the 3 page back, there is no need to keep 
	 * later 3 pages. Newer step will be added after it).
	 *
	 * If we trying to add new step to the list with the length grater than 
	 * MAX_PATH, least recently used step will be removed with the next on the list.
	 *
	 * @see setTitle
	 * @see setDescription
	 */
	function addStep($page_name,$title = null,$description = null)
	{
		if(!isset($page_name)) return;

		if(!isset($title))
			$title = requestURI();
		if( ($this->controller_name == "index" && $page_name=="index")	|| 
			!isset($this->user_path[0]) ||  empty($this->user_path) )
		{
			$this->user_path = array();
			$this->user_path[0]['url'] = requestURI(1);
			$this->user_path[0]['title'] = $title;
			$this->user_path[0]['desription'] = $description;
			$this->user_path[0]['page'] = $page_name;
			$this->user_path[0]['controller'] = $this->controller_name;
		}
		else
		{
            $to_add = 1;
            for($i = 0, $c = count($this->user_path); $i < $c; $i++)
				if(isset($this->user_path[$i]) && isset($this->user_path[$i]['page']) &&  $this->user_path[$i]['page'] == $page_name
					&& $this->user_path[$i]['controller'] == $this->controller_name	)
				{
                    $this->user_path = array_slice($this->user_path,$i);
                    $this->user_path[0]['url'] = requestURI(1);
                    $this->user_path[0]['page'] = $page_name;
                    $to_add = false;
					break;
                }
            if($to_add)
            {
                if(count($this->user_path) == self::MAX_PATH)
                    $this->user_path = array_slice($this->user_path,0,-1);
                array_unshift($this->user_path,array(
                    "url"=>requestURI(1),
                    "title"=>$title,
                    "desription"=>$description,
                    "page"=>$page_name,
                    "controller"=>$this->controller_name
                ));
            }
		}
        $this->storage->set("user_path",$this->user_path);
	}
	//}}}

	//{{{ getStep
	/**
	 * Return step, specified by the passed index.
	 *
	 * Result is string-based array with "url", "title",
	 * "description", "page", "controller" keys.
	 *
	 * @param int step index: 0 -- current page, 1 -- previous, etc
	 * @return array resulting array or null, if such step hasn't been found.
	 * @see getStepURL
	 */
	function getStep($step)
	{
		$step = abs($step);
		if($step >= count($this->user_path)) return /*isset($this->user_path[0])?$this->user_path[0]:*/null;
		return $this->user_path[$step];
	}
	//}}}

	//{{{ getStepURL
	/**
	 * Return the URL of the step, specified by the passed index.
	 *
	 * @param int step index: 0 -- current page, 1 -- previous, etc
	 * @return string resulting URL or empty string, if such step hasn't been found.
	 * @see getStep
	 */
    function getStepURL($step)
    {
        $s = $this->getStep($step);
        return is_array($s) && isset($s['url'])?$s['url']:"";
	}
	//}}}

	//{{{ getSteps
	/**
	 * Returns list of steps in reverse order, representing steps history
	 * in natural way: from left to right.
	 *
	 * @param null
	 * @return array of string-based arrays with "url", "title",
	 * "description", "page", "controller" keys.
	 */
	function getSteps()
	{
		return array_reverse($this->user_path);
	}
	//}}}

	//{{{ injectSteps
	/** 
	 * Replaces current steps-list with given, reversing it 
	 * before assigning.
	 *
	 * @param array of string-based arrays with "url", "title",
	 * "description", "page", "controller" keys.
	 * @return null
	 */
	function injectSteps(array $steps)
	{
		if(!count($steps)) return;
		$this->user_path = array_reverse($steps);
        $this->storage->set("user_path",$this->user_path);
	}
	//}}}

	//{{{ clean
	/**
	 * Cleans up steps list
	 *
	 * @param null
	 * @return null
	 */
	function clean()
	{
		$this->user_path = array();
		$this->storage->un_set("user_path");
	}
	//}}}

	//{{{ setTitle
	/**
	 * Setting title for the given step.
	 *
	 * @param int index of the step. 0 means current page, 1 -- previous etc
	 * @param string title to set
	 * @return null
	 */
	function setTitle($step, $title)
	{
		if(!is_numeric($step) || !is_string($title)) return;

		if($step >= count($this->user_path)	|| !isset($this->user_path[$step])) return;
		$this->user_path[$step]['title'] = Filter::apply($title,STRING_ENCODE);
		$this->storage->set("user_path",$this->user_path);
	}
	//}}}
	//{{{ setDescription
	/**
	 * Setting description for the given step.
	 *
	 * @param int index of the step. 0 means current page, 1 -- previous etc
	 * @param string title to set
	 * @return null
	 */
	function setDescription($step, $description)
	{
		if(!is_numeric($step) || !is_string($title)) return;

		if($step >= count($this->user_path)	|| !isset($this->user_path[$step])) return;
		$this->user_path[$step]['description'] = Filter::apply($description,STRING_ENCODE);
		$this->storage->set("user_path",$this->user_path);
	}
	//}}}
}
//}}}
?>
