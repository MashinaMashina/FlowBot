<?php

class Welcome extends FB_VK_Controller
{
	public function index()
	{
		// Отправляем первое сообщение пользователю
		$this->vkutil->reply("Приветствуем!\r\n\r\nЭто FlowBot!");
	}
}