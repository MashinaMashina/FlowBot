<?php

class FB_VK_Controller extends CI_Model {
	
	public function __construct()
	{
		$this->load->library('vk');
		$this->vk->set_access_token($this->config->item('access_token'));
	}
	
}