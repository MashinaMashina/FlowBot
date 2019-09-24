<?php

class Menu extends FB_VK_Controller{
	public function index()
	{
		// Загружаем хелпер с генератором клавиатуры
		$this->load->helper('vk_keyboard');
		
		// Создаем кнопку для клавиатуры
		$btn_plus = new VK_Button;
		$btn_plus->action()
			->type('text')
			->label('Прибавить')
			->payload('{"command":"plus"}');
		$btn_plus->color('primary');
		
		// Еще кнопка
		$btn_minus = new VK_Button;
		$btn_minus->action()
			->type('text')
			->label('Вычесть')
			->payload('{"command":"minus"}');
		$btn_minus->color('primary');
		
		// Еще кнопка
		$btn_multiple = new VK_Button;
		$btn_multiple->action()
			->type('text')
			->label('Умножить')
			->payload('{"command":"multiple"}');
		$btn_multiple->color('secondary');
		
		// Еще кнопка
		$btn_division = new VK_Button;
		$btn_division->action()
			->type('text')
			->label('Разделить')
			->payload('{"command":"division"}');
		$btn_division->color('secondary');
		
		// Собираем кнопки в клавиатуру
		$keyboard = new VK_Keyboard;
		$keyboard
			->one_time(true)
			->buttons([
				[$btn_plus, 	$btn_minus		],
				[$btn_multiple, $btn_division	]
			]);
		
		// Помещаем клавиатуту в массив для отправки в ВК
		$data = [
			'keyboard' => (string)$keyboard
		];
		
		// Посылаем сообщение пользователю
		$this->vkutil->reply('Выбери пункт меню', $data);
		
		// При приходе следующего сообщения грузить контроллер Menu/choose
		return 'Menu/choose';
	}
	
	public function choose()
	{
		// Если пользователь не нажимал на клавиатуру, а отравил что-то другое
		if ( ! isset($this->phpinput->object->payload->command))
		{
			return $this->load->controller('Menu');
		}
		
		// Получаем команду с кнопки
		$operation = $this->phpinput->object->payload->command;
		
		// Сохраняем команду в сессии
		$this->fbsession->set_userdata('operation', $operation);
		
		// Загружаем контроллер операций
		return $this->load->controller('Operation');
	}
}