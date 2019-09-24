<?php

class Operation extends FB_VK_Controller{
	public function index()
	{
		$operations = [
			'minus' => 'вычитание',
			'plus' => 'сложение',
			'multiple' => 'умножение',
			'division' => 'деление'
		];
		
		// Берем операцию из сессии
		$operation = $this->fbsession->userdata('operation');
		
		$this->vkutil->reply("Вы выбрали {$operations[$operation]}.\r\n\r\nВведите первое число");
		
		return 'Operation/first';
	}
	
	public function first()
	{
		$first = $this->phpinput->object->text;
		
		if( !is_numeric($first))
		{
			$this->vkutil->reply('Требуется указать число');
			return;
		}
		
		$this->fbsession->set_userdata('first', $first);
		
		$this->vkutil->reply('Введите второе число');
		
		return 'Operation/second';
	}
	
	public function second()
	{
		$first = $this->fbsession->userdata('first');
		$second = $this->phpinput->object->text;
		
		if( !is_numeric($second))
		{
			$this->vkutil->reply('Требуется указать число');
			return;
		}
		
		$operation = $this->fbsession->userdata('operation');
		
		switch($operation)
		{
			case 'minus':
				$result = $first - $second;
			break;
			
			case 'plus':
				$result = $first + $second;
			break;
			
			case 'multiple':
				$result = $first * $second;
			break;
			
			case 'division':
				if( $second == 0)
				{
					$second = 1;
				}
			
				$result = $first / $second;
			break;
		}
		
		$this->vkutil->reply("Результат: {$result}");
		
		return $this->load->controller('Menu');
	}
}