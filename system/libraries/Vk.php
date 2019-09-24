<?php

class CI_Vk {
	
	private $access_token;
	private $version = '5.101';
	
	public function __construct()
	{
		get_instance()->load->helper('request');
		get_instance()->config->load('lib_vk', TRUE, TRUE);
	}
	
	public function set_access_token($access_token)
	{
		$this->access_token = $access_token;
	}
	
	public function set_version($version)
	{
		$this->version = $version;
	}
	
	public function unit_api( $query=array()){
		$q = "return {\n";
		$c = 0;
		
		if( !is_array($query))
			var_dump($query);
		
		foreach( $query as $v ){
			$q .= '"'. $c .'": API.' .$v[0] .'({' .$this->unit_params( $v[1] ) .'}),' ."\n";
			++$c;
		}
		$q = substr( $q, 0, -1 );
		$q .= '};';

		$res = $this->api( 'execute', array( 'code' => $q ) );

		return $res;
	}

	private function unit_params( $params ){
		$pice = array();
		foreach( $params as $k => $v ){
			$pice[] = '"' . $k .'": "' . $v .'"';
		}
		$res = implode( ",", $pice );
		return $res;
	}
	
	public function api($method, $params=array(), $options = array()) {
		$params['v'] = $this->version;
		$params['format'] = 'json';
		$params['random'] = rand(0,9);
		
		if( !isset($options['access_token']))
		{
			$params['access_token'] = $this->access_token;
		}
		else
		{
			if ($options['access_token'] === 'util' and $access_token = get_instance()->config->item('util_access_token', 'lib_vk'))
			{
				$params['access_token'] = $access_token;
			}
			else
			{
				$params['access_token'] = $options['access_token'];
			}
		}
		
		$url = 'https://api.vk.com/method/'.$method;
		
		$n = 0;
		
		do
		{
			if( $n >= 30)
			{
				echo $request->error();
				log_message('error', 'VK API requests count >= 30');
			}
			$n++;
			
			$request = new request($url);
			$request->post($params);
			$request->send();
			print_r($request->response);
			$res = $request->response;
			
			if( $request->info['http_code'] == 414)
			{
				echo $request->dump();
				echo '414: '.print_r($method, true).print_r($params, true);
				die();
			}
			
			sleep(0.1);
		}
		while( $request->error());
		
		$res = json_decode($res);
		
		if( isset($res->error))
		{
			switch($res->error->error_code)
			{
				case 9:
				case 13:
				case 6:
					sleep(1);
					$res = $this->api($method, $params);
				break;
				
				case 14:
					get_instance()->load->helper('antigate');
					
					$params['captcha_sid'] = $res->error->captcha_sid;
					$params['captcha_key'] = antigate::read($res->error->captcha_img);
					
					$res = $this->api($method, $params);
				break;
			}
			
		}
		
		return $res;
	}
	
	public function upload_photo($src, $item_id = false)
	{
		$group_id = false;
		$user_id = false;
		
		if ($item_id < 0)
		{
			$group_id = -1 * $item_id;
		}
		else 
		{
			$user_id = $item_id;
		}
		
		$data = [];
		
		if ($group_id)
			$data['group_id'] = $group_id;
		
		$upload_url = $this->api('photos.getWallUploadServer', $data);
		$upload_url = $upload_url->response->upload_url;

		if (is_array($src))
		{
			foreach( $src as $v )
			{
				$return[] = $this->upload_photo($v, $item_id);
			}
			return implode(',', $return);
		}
		else
		{
			
			$post_fields = array( 'photo' => function_exists('curl_file_create') ? curl_file_create( $src ) : '@'.realpath( $src ) );

			$request = new request($upload_url);
			$request->post($post_fields);
			$request->send();
			
			$json = JSON_DECODE($request->response);
			if ($json->photo == '' or $server = $json->server == '' or $hash = $json->hash == '')
			{
				return false;
			}
			
			$photo = $json->photo;
			$server = $json->server;
			$hash = $json->hash;

			$data = [
				'photo' => $photo,
				'server' => $server,
				'hash' => $hash 
			];
			
			if( $group_id)
			{
				$data['group_id'] = $group_id;
			}
			
			if( $user_id)
			{
				$data['user_id'] = $user_id;
			}
			
			$json = $this->api('photos.saveWallPhoto', $data );
			
			if( !isset($json->response) or !$json->response[0]->id ){ return false; }
			
			$return = 'photo' . $json->response[0]->owner_id . '_' . $json->response[0]->id;
			
			return $return;
			
		}
	}
	
	public function upload_photo_im($photo)
	{
		static $upload_url;
		
		if( !isset( $upload_url))
		{
			$result = $this->api('photos.getMessagesUploadServer');
			
			if( !isset($result->response->upload_url)) log_message('error', 'photos.getMessagesUploadServer: '.$result['error']['error_msg']);
			
			$upload_url = $result->response->upload_url;
		}
		
		$curl_file = (function_exists('curl_file_create') ? curl_file_create($photo) : '@'.realpath($photo));
		$post_fields = array( 'photo' => $curl_file );
		
		$request = new request($upload_url);
		$request->set(CURLOPT_POSTFIELDS, $post_fields);
		$request->send();
		
		$result = json_decode($request->response, true);
		
		$result = $this->api('photos.saveMessagesPhoto', array('photo' => $result['photo'], 'server' => $result['server'], 'hash' => $result['hash']));
		
		if( !isset($result->response[0]->owner_id))
		{
			var_dump($result);
		}
		
		return "photo{$result->response[0]->owner_id}_{$result->response[0]->id}";
	}
	
	public function upload_video_yt($name, $descr, $video_link)
	{
		$data = [
			'name' => $name,
			'descr' => $descr,
			'link' => $video_link
		];
		
		$result = $this->api('video.save', $data);
		
		$request = new request($result->response->upload_url);
		$request->send();
		
		return "video{$result->response->owner_id}_{$result->response->video_id}";
	}
}