<?php

class CI_Vkutil {
	
	public $has_step = [];
	public $start_controller = [];
	
	private $routes;
	
	public function reply($message, $data = [])
	{
		return $this->reply_to(get_instance()->phpinput->object->from_id, $message, $data);
	}
	
	public function reply_to($user_id, $message, $data = [])
	{
		$data = $data + [
			'message' => $message,
			'user_id' => $user_id,
			'random_id' => $this->mess_random_id()
		];
		
		$r = get_instance()->vk->api('messages.send', $data);
	
		return $r;
	}
	
	public function mess_random_id()
	{
		return get_instance()->config->item('id', 'step').rand(0, 2000000);
	}
	
	public function get_start_controller($user_id, $group_id)
	{
		$phpinput = (object)[
			'object' => (object)[
				'from_id' => $user_id
			],
			'group_id' => $group_id
		];
		
		return $this->fb_start_controller($user_id, FALSE, $phpinput);
	}
	
	public function fb_start_controller($user_id = NULL, $default_mode = FALSE, $phpinput = NULL)
	{
		$CI = &get_instance();
		$this->has_step[$user_id] = false;
		$phpinput = isset($phpinput) ? $phpinput : NULL;
		
		if( !isset($phpinput) and isset($CI->phpinput))
		{
			$phpinput = $CI->phpinput;
		}
		
		if( !isset($phpinput))
		{
			log_message('error', 'Cant find phpinput in Vkutil library (fb_start_controller)');
			return FALSE;
		}
		
		if (empty($user_id))
		{
			$user_id = $phpinput->object->from_id;
		}
		
		if ( ! empty($this->start_controller[$user_id]))
		{
			return $this->start_controller[$user_id];
		}
		
		$CI->config->load('platforms/codes', 'platform_code');
		$CI->load->database();
		$CI->load->library('fbsession');
		if	($default_mode)
		{
			$CI->fbsession->set_user_id($user_id);
		}
		
		$CI->db->where('platform', $CI->config->item('VK', 'platform_code'));
		$CI->db->where('project', $phpinput->group_id);
		$CI->db->where('user_id', $user_id);
		$step = $CI->db->get($CI->config->item('step_table'))->row_array();
		
		$CI->config->set_item('step', $step);
		
		$CI->fbsession->load($step['params'], $user_id);
		
		if ( ! $step or empty($step))
		{
			$start_controller = $this->routes['default_controller'];
		}
		else
		{
			$this->has_step[$user_id] = true;
			
			$start_controller = $step['controller'];
		}
		
		return $start_controller;
	}
	
	public function save_step($step, $user_id, $group_id)
	{
		$phpinput = (object)[
			'object' => (object)[
				'from_id' => $user_id
			],
			'group_id' => $group_id
		];
		
		return $this->fb_save_step($step, $user_id, $phpinput);
	}
	
	public function fb_save_step($step, $user_id = NULL, $phpinput = NULL)
	{
		$CI = &get_instance();
		
		$phpinput = isset($phpinput) ? $phpinput : NULL;
		
		if( !isset($phpinput) and isset($CI->phpinput))
		{
			$phpinput = $CI->phpinput;
		}
		
		if( !isset($phpinput))
		{
			log_message('error', 'Cant find phpinput in Vkutil library (fb_save_step)');
			return FALSE;
		}
		
		if (empty($user_id))
		{
			$user_id = $phpinput->object->from_id;
		}
		
		log_message('debug', 'Save step "'.(isset($step['controller']) ? $step['controller'] : '').'"; User ID: '.$user_id);
		
		if( !isset($this->has_step[$user_id]))
		{
			$this->get_start_controller($user_id, $phpinput->group_id);
		}
		
		var_dump($this->has_step);
		
		$step['time'] = time();
		if ($this->has_step[$user_id])
		{
			$CI->db->where('platform', $CI->config->item('VK', 'platform_code'));
			$CI->db->where('project', $phpinput->group_id);
			$CI->db->where('user_id', $user_id);
			
			$CI->db->update($CI->config->item('step_table'), $step);
		}
		else
		{
			$step['platform'] = $CI->config->item('VK', 'platform_code');
			$step['project'] = $phpinput->group_id;
			$step['user_id'] = $user_id;
			
			$CI->db->insert($CI->config->item('step_table'), $step);
		}
	}
	
	public function load_routes()
	{
		if( isset($this->routes))
		{
			return $this->routes;
		}
		
		$route = [
			'default_controller' => 'Welcome/index',
			'default_static_controller' => 'Static_welcome/index'
		];
		$this->routes = [];
		
		// Load the routes.php file. It would be great if we could
		// skip this for enable_query_strings = TRUE, but then
		// default_controller would be empty ...
		if (file_exists(APPPATH.'config/fb_routes.php'))
		{
			include(APPPATH.'config/fb_routes.php');
		}

		if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/fb_routes.php'))
		{
			include(APPPATH.'config/'.ENVIRONMENT.'/fb_routes.php');
		}
		
		$this->routes = $route;
		
		return $this->routes;
	}
}