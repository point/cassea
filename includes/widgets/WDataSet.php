<?php
//
/*
* General descriprion
*
* PHP versions 4
*
* @package
* @subpackage
* author
* @version
* @link http://webaroundyou.com Home page
*
*/
// $Id: WDataSet.php 1020 2008-03-19 17:24:58Z point $
//
//require_once("WObject.php");
/**
* Class description
*/
//{{{ WDataSet
class WDataSet extends WObject
{
    var

        /**
        * @var      string
        */
        $name = null,
        /**
        * @var      string
        */
        $classname = null,
        /**
        * @var      string
        */
        $datasource = null,
        /**
        * @var      array
        */
        $params = array(),
        /**
        * @var      string
        */
        $label = "l",
        /**
        * @var      boolean
        */
        $preload = 0,
        /**
        * @var      mixed
        */
		$preload_param = null,
		/**
		* @var boolean
		*/
		$dont_use_oid = 0,
  		/**
        * @var      bool
        */
		$static = 0,
        /**
        * @var      array
        */
        $cache = array()
		    ;
    // {{{ WDataSet 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    */
    function WDataSet()
    {
		parent::WObject();
    }
    // }}}
    // {{{ GetData
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    */
    function getData()
	{
		$this->cache = null;
		if(!empty($this->cache))
			return $this->cache;
		if(empty($this->classname) || empty($this->datasource))
    	{
			$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,"Required fields are empty"),LOG_LEVEL_ERROR);
			return;
    	}
		if($this->static )
		{
			Core::require_class($this->classname);
			$arr = array();
            if(empty($this->params['oid'])) $this->params['oid'] = $this->findOID();
			$all_params = &$this->params;
			for($i = 0; $i < count($this->user_func_params); $i++)
				if(isset($this->user_func_params[$i]['variable']))
				{
					if($this->user_func_params[$i]['type'] == "numeric"
						&& isset($all_params[$this->user_func_params[$i]['variable']])
						&& is_numeric($all_params[$this->user_func_params[$i]['variable']]))
							$arr[] = $this->user_func_params[$i]['assigned_value'] = 0 + $all_params[$this->user_func_params[$i]['variable']];

					elseif($this->user_func_params[$i]['type'] == "string"
						&& isset($all_params[$this->user_func_params[$i]['variable']])
						&& is_string($all_params[$this->user_func_params[$i]['variable']]))
							$arr[] = $this->user_func_params[$i]['assigned_value'] = "".$all_params[$this->user_func_params[$i]['variable']];
					
					elseif($this->user_func_params[$i]['type'] == "array"
						&& isset($all_params[$this->user_func_params[$i]['variable']])
						&& is_array($all_params[$this->user_func_params[$i]['variable']]))
							$arr[] = $this->user_func_params[$i]['assigned_value'] = $all_params[$this->user_func_params[$i]['variable']];

					elseif($this->user_func_params[$i]['type'] == "all_params")
						$arr[] = $this->user_func_params[$i]['assigned_value'] = $all_params;
				}
				elseif(isset($this->user_func_params[$i]['constant']))
					$arr[] = $this->user_func_params[$i]['assigned_value'] = $this->user_func_params[$i]['constant'];

			return $this->cache = WHelper::trim(call_user_func_array(array($this->classname,$this->datasource),$arr));
		}
		else
		{
			$db_params = Core::require_class($this->classname);
			$this->params = array_merge($db_params,$this->params);
			$c_name = $this->classname;
			$oid = $this->findOID();
			$this->setParams(array_merge($this->params,array("id" => $oid,"oid"=>$oid)));
			if(!empty($oid) && !$this->dont_use_oid)
				$obj = Core::load_object_by_oid($oid,$this->params,true);
			else $obj = new $c_name($this->params);
			if(!is_a($obj,$this->classname)) 
			{
				$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,"Fail to create object"),LOG_LEVEL_ERROR);
				return;
			}		
			if(!method_exists($obj,$this->datasource))
			{
				$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,"Method doesn't exists"),LOG_LEVEL_ERROR);
				return;
			}
			if(method_exists($obj,"load") && $this->preload)
				$obj->load($this->preload_param);
			$f_name = $this->datasource;
			return $this->cache = WHelper::trim($obj->$f_name($this->params));
		}
	}
    //}}}
	function findOID()
	{
		$site_user = &User::get();
		$controller = &CController::getInstance();
		$oid = null;
		
		if(!empty($this->params['oid']) && is_numeric($this->params['oid']))
		{
			$oid = 0+$this->params['oid'];
			$storage = new CVarStorage("content", md5($site_user->get_session_id()), Core::get_settings("session_length"));
			$storage->set("current_oid",$this->params['oid']);
		}
		elseif(!empty($controller->all_params['get']['oid']))
		{
			$oid = 0+$controller->all_params['get']['oid'];
            $storage = new CVarStorage("content", md5($site_user->get_session_id()), Core::get_settings("session_length"));
			$storage->set("current_oid",$oid);
		}
		else
		{
			$storage = new CVarStorage("content", md5($site_user->get_session_id()), Core::get_settings("session_length"));
			$t_oid = $storage->get("current_oid");
			if(!empty($t_oid) && is_numeric($t_oid))
				$oid = $t_oid;
			else
			{
				$t_oid = $storage->get("parent_oid");
				if(!empty($t_oid) && is_numeric($t_oid))
					$oid = $t_oid;
			}
			$oid = 0+$oid;

		}
		return $oid;
	}
    // {{{ setName 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $name    
    * @return   void
    */
    function setName($name)
    {
       	if(!isset($name)) 
		{
	   		$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,"Incompatible name format"),LOG_LEVEL_ERROR);
			return;
		}
		$this->name = $name;

    }
    // }}}
    
    // {{{ getName 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getName()
    {
		return $this->name;
    }
    // }}}
    
    // {{{ setClassname 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $classname    
    * @return   void
    */
    function setClassname($classname)
    {
       	if(!isset($classname)) 
		{
	   		$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,"Incompatible classname format"),LOG_LEVEL_ERROR);
			return;
		}
		$this->classname = $classname;
    }
    // }}}
    
    // {{{ getClassname 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getClassname()
    {
		return $this->classname;
    }
    // }}}
    // {{{ setPreload
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $preload
    * @return   void
    */
    function setPreload($preload)
    {
       	if(!isset($preload)) 
		{
	   		$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,"Parameter is empty"),LOG_LEVEL_WARNING);
			return;
		}
		$this->preload = $preload;
    }
    // }}}
    
    // {{{ getPreload
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   boolean
    */
    function getPreload()
    {
		return $this->preload;
    }
    // }}}
    // {{{ setPreloadParam
    /**
    * Method description
    *
    * More detailed method description
    * @param    mixed $param
    * @return   void
    */
    function setPreloadParam($preload_param)
    {
       	if(!isset($preload_param)) 
		{
	   		$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,"Parameter is empty"),LOG_LEVEL_WARNING);
			return;
		}
		$this->preload_param = $preload_param;
    }
    // }}}
    
    // {{{ getPreloadParam
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   mixed
    */
    function getPreloadParam()
    {
		return $this->preload_param;
    }
    // }}}
    
    // {{{ setDatasource 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $datasource    
    * @return   void
    */
    function setDatasource($datasource)
    {
       	if(!isset($datasource)) 
		{
	   		$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,"Incompatible classname format"),LOG_LEVEL_ERROR);
			return;
		}
		$this->datasource = $datasource;
    }
    // }}}
    
    // {{{ getDatasource 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getDatasource()
    {
		return $this->datasource;
    }
    // }}}
    
    // {{{ setParams 
    /**
    * Method description
    *
    * More detailed method description
    * @param    array $params    
    * @return   void
    */
    function setParams($params)
    {
		if(!is_array($params) || empty($params))
		{
	   		$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,"params are empty or have incorrect format"),LOG_LEVEL_WARNING);
			return;
		}	
		$this->params = $params;
    }
    // }}}
    
    // {{{ getParams 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   array
    */
    function getParams()
    {
		return $this->params;
    }
    // }}}
    
    // {{{ setLabel 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $label    
    * @return   void
    */
    function setLabel($label)
    {
    	if(!isset($label)) 
		{
	   		$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,"Incompatible label format"),LOG_LEVEL_ERROR);
			return;
		}

		$this->label = $label;
    }
    // }}}
    
    // {{{ getLabel 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getLabel()
    {
		return $this->label;
    }
	// }}}
	// {{{ setDontUseOid
    /**
    * Method description
    *
    * More detailed method description
    * @param   bool
    * @return   void
    */
    function setDontUseOid($state)
    {
       	if(isset($state)) 
			$this->dont_use_oid = 0 + $state;
    }
    // }}}
    
    // {{{ getDontUseOid
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   bool
    */
    function getDontUseOid()
    {
		return $this->dont_use_oid;
    }
    // }}}    
	// {{{ setStatic
    /**
    * Method description
    *
    * More detailed method description
    * @param   bool
    * @return   void
    */
    function setStatic($static)
    {
		if(isset($static))
			$this->static = 0 + $static;
    }
    // }}}
    
    // {{{ getStatic
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   bool
    */
    function getStatic()
    {
		return $this->static;
    }
    // }}}    
	// {{{ setUserFuncParams
    /**
    * Method description
    *
    * More detailed method description
    * @param   bool
    * @return   void
    */
    function setUserFuncParams($params)
    {
       	if(empty($params) || !is_array($params)) 
       		$this->user_func_params = array();
       	else $this->user_func_params = $params;
    }
	// }}}

}
//}}}
?>
