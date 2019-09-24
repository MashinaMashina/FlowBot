<?php

get_instance()->load->helper('json_constructor');

class VK_Button_Action extends FB_JSON_Constructor {
	
	public function __call($name, $arguments)
	{
		if($name === 'label' and mb_strlen($arguments[0]) > 40)
		{
			$arguments[0] = mb_substr($arguments[0], 0, 38).'..';
		}
		
		return parent::__call($name, $arguments);
	}
	
}

class VK_Button extends FB_JSON_Constructor{

	public function __construct()
	{
		$this->action = new VK_Button_Action;
	}
}

class VK_Keyboard extends FB_JSON_Constructor {}