<?php

class Route extends Major_CI_Controller {
	
	private $method = 'index';
	private $routes;
	private $start_controller = [];

	public $phpinput;
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function init()
	{
		$this->load->helper('file_helper');
		$this->load->library('vkutil');
		
		$this->phpinput = read_file('php://input');
		
		if (empty($this->phpinput))
		{
			log_message('error', 'Can\'t find VK input data (php://input)');
		}
		
		log_message('info', 'PHPINPUT: '.$this->phpinput);
	
		$this->phpinput = json_decode($this->phpinput);
		$this->phpinput->group_id = intval($this->phpinput->group_id);
		$this->config->set_item('project_dir', 'VK/'.$this->phpinput->group_id);
		
		if (isset($this->phpinput->object->payload))
		{
			$decoded = json_decode($this->phpinput->object->payload);
			
			if (json_last_error() === JSON_ERROR_NONE)
			{
				$this->phpinput->object->payload = $decoded;
			}
		}
		
		require_once APPPATH.'config/platforms/VK/config.php';
		
		$this->config->merge($config[$this->phpinput->group_id]);
		
		if ($this->phpinput->type === 'confirmation')
		{
			$this->config->set_item('allow_output', TRUE);
			echo $this->config->item('confirmation_code');
			exit;
		}
		
		if ($this->phpinput->type !== 'message_new')
		{
			log_message('debug', 'Unknown request type: '.$this->phpinput->type);
			echo $this->item->config('output');
			exit;
		}
		
		$routes = $this->vkutil->load_routes();
		
		$user_id = $this->phpinput->object->from_id;
		
		$this->vkutil->has_step[$user_id] = false;
		if ($this->config->item('route') === 'static')
		{
			$this->start_controller[$user_id] = $routes['default_static_controller'];
		}
		else // Active route
		{
			$this->start_controller[$user_id] = $this->vkutil->fb_start_controller($user_id, TRUE);
		}
		
		return $this->start_controller[$user_id];
	}
	
	public function start()
	{
		$user_id = $this->phpinput->object->from_id;
	
		$finish_controller = $this->load->controller($this->start_controller[$user_id]);
		
		if ($this->config->item('route') === 'static')
		{
			return;
		}
		
		$step = [];
		if ( ! empty($finish_controller) and $finish_controller !== $this->start_controller[$user_id])
		{
			$step['controller'] = $finish_controller;
		}
		
		$session = $this->fbsession->save_data($user_id);
		if ($this->fbsession->has_change)
		{
			$step['params'] = $session;
		}
		
		if( !count($step))
		{
			return;
		}
		
		$this->vkutil->fb_save_step($step, $user_id);
	}
}