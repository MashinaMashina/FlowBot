# Создание своего бота
Как и в CodeIgniter, тут действует схема MVC (Model-View-Controller), только так как это чат-бот, view отсутствует.

В MVC работа приложения начинается с контроллеров, они хранятся в папке /application/controllers. Контролеры для ботов ВК уже лежат в подпапке VK.

 У каждой группы своя папка с контроллерами, имя папки - это ID группы ВКонтакте.

При приходе сообщения от нового пользователя, запускается, в зависимости от настроек, контроллер Welcome/index или Static_welcome/index. Далее будем рассматривать на примере Welcome/index.

Если ID группы у нас 777, то этот контроллер будет лежать в файле */application/controllers/VK/777/Welcome.php*, а его содержание будет 	таким:

    <?php
    class Welcome extends FB_VK_Controller
    {
    	public function index()
    	{
    	}
    }
В ответ на первое сообщение отправим пользователю ответ.

    <?php
    class Welcome extends FB_VK_Controller
    {
    	public function index()
    	{
	    	$this->vkutil->reply('Привет!');
    	}
    }

А теперь сделаем так, чтобы на повторное сообщение человек уже не получал "Привет".

    <?php
    class Welcome extends FB_VK_Controller
    {
    	public function index()
    	{
    		$this->vkutil->reply('Привет!');
    		
    		return 'Welcome/stop';
    	}
    	
    	public function stop()
    	{
    		$this->vkutil->reply('Стоп! Мы тебе уже говорили "Привет"');
    	}
    }

Из контроллера Welcome/index мы вернули в систему контроллер Welcome/stop, который и будет запускаться при следующем сообщении.

Welcome/stop будет запускаться до тех пор, пока он не вернет какой-то иной контроллер.
