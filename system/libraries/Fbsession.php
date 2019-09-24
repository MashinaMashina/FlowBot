<?php

class CI_Fbsession {
	
	protected $_fingerprint = [];
	protected $userdata = [];
	protected $default_user_id;
	public $has_change = FALSE;
	
	public function __construct()
	{
		log_message('info', 'FB Session Class Initialized');
	}
	
	public function set_user_id($user_id)
	{
		$this->default_user_id = $user_id;
	}
	
	public function load($data, $user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
		
		$this->_fingerprint[$user_id] = md5($data);
		$this->userdata[$user_id] = unserialize($data);
		
		//echo 123;
		//print_R($this->userdata);
		
		if( empty($this->userdata[$user_id]))
		{
			$this->userdata[$user_id] = [];
		}
		
		$this->_ci_init_vars($user_id);
	}
	
	public function save_data($user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		$this->_ci_finish_vars($user_id);
		
		echo 'Userdata all: ';
		print_r($this->userdata);
		
		$data = serialize($this->userdata[$user_id]);
		
		if( md5($data) !== $this->_fingerprint[$user_id])
		{
			$this->has_change = TRUE;
		}
		
		return $data;
	}
	
	/**
	 * Handle temporary variables
	 *
	 * Clears old "flash" data, marks the new one for deletion and handles
	 * "temp" data deletion.
	 *
	 * @return	void
	 */
	protected function _ci_init_vars($user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		if ( ! empty($this->userdata[$user_id]['__ci_vars']))
		{
			$current_time = time();

			foreach ($this->userdata[$user_id]['__ci_vars'] as $key => &$value)
			{
				if ($value === 'new')
				{
					$this->userdata[$user_id]['__ci_vars'][$key] = 'old';
				}
				// Hacky, but 'old' will (implicitly) always be less than time() ;)
				// DO NOT move this above the 'new' check!
				elseif ($value < $current_time)
				{
					unset($this->userdata[$user_id][$key], $this->userdata[$user_id]['__ci_vars'][$key]);
				}
			}

			if (empty($this->userdata[$user_id]['__ci_vars']))
			{
				unset($this->userdata[$user_id]['__ci_vars']);
			}
		}
	}

	// ------------------------------------------------------------------------

	protected function _ci_finish_vars($user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		if ( ! empty($this->userdata[$user_id]['__ci_vars']))
		{
			foreach ($this->userdata[$user_id]['__ci_vars'] as $key => &$value)
			{
				if ($value === 'old')
				{
					unset($this->userdata[$user_id][$key], $this->userdata[$user_id]['__ci_vars'][$key]);
				}
			}

			if (empty($this->userdata[$user_id]['__ci_vars']))
			{
				unset($this->userdata[$user_id]['__ci_vars']);
			}
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Mark as flash
	 *
	 * @param	mixed	$key	Session data key(s)
	 * @return	bool
	 */
	public function mark_as_flash($key, $user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		if (is_array($key))
		{
			for ($i = 0, $c = count($key); $i < $c; $i++)
			{
				if ( ! isset($this->userdata[$user_id][$key[$i]]))
				{
					return FALSE;
				}
			}

			$new = array_fill_keys($key, 'new');

			$this->userdata[$user_id]['__ci_vars'] = isset($this->userdata[$user_id]['__ci_vars'])
				? array_merge($this->userdata[$user_id]['__ci_vars'], $new)
				: $new;

			return TRUE;
		}

		if ( ! isset($this->userdata[$user_id][$key]))
		{
			return FALSE;
		}

		$this->userdata[$user_id]['__ci_vars'][$key] = 'new';
		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get flash keys
	 *
	 * @return	array
	 */
	public function get_flash_keys($user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		if ( ! isset($this->userdata[$user_id]['__ci_vars']))
		{
			return array();
		}

		$keys = array();
		foreach (array_keys($this->userdata[$user_id]['__ci_vars']) as $key)
		{
			is_int($this->userdata[$user_id]['__ci_vars'][$key]) OR $keys[] = $key;
		}

		return $keys;
	}

	// ------------------------------------------------------------------------

	/**
	 * Unmark flash
	 *
	 * @param	mixed	$key	Session data key(s)
	 * @return	void
	 */
	public function unmark_flash($key, $user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		if (empty($this->userdata[$user_id]['__ci_vars']))
		{
			return;
		}

		is_array($key) OR $key = array($key);

		foreach ($key as $k)
		{
			if (isset($this->userdata[$user_id]['__ci_vars'][$k]) && ! is_int($this->userdata[$user_id]['__ci_vars'][$k]))
			{
				unset($this->userdata[$user_id]['__ci_vars'][$k]);
			}
		}

		if (empty($this->userdata[$user_id]['__ci_vars']))
		{
			unset($this->userdata[$user_id]['__ci_vars']);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Mark as temp
	 *
	 * @param	mixed	$key	Session data key(s)
	 * @param	int	$ttl	Time-to-live in seconds
	 * @return	bool
	 */
	public function mark_as_temp($key, $ttl = 300, $user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		$ttl += time();

		if (is_array($key))
		{
			$temp = array();

			foreach ($key as $k => $v)
			{
				// Do we have a key => ttl pair, or just a key?
				if (is_int($k))
				{
					$k = $v;
					$v = $ttl;
				}
				else
				{
					$v += time();
				}

				if ( ! isset($this->userdata[$user_id][$k]))
				{
					return FALSE;
				}

				$temp[$k] = $v;
			}

			$this->userdata[$user_id]['__ci_vars'] = isset($this->userdata[$user_id]['__ci_vars'])
				? array_merge($this->userdata[$user_id]['__ci_vars'], $temp)
				: $temp;

			return TRUE;
		}

		if ( ! isset($this->userdata[$user_id][$key]))
		{
			return FALSE;
		}

		$this->userdata[$user_id]['__ci_vars'][$key] = $ttl;
		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get temp keys
	 *
	 * @return	array
	 */
	public function get_temp_keys($user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		if ( ! isset($this->userdata[$user_id]['__ci_vars']))
		{
			return array();
		}

		$keys = array();
		foreach (array_keys($this->userdata[$user_id]['__ci_vars']) as $key)
		{
			is_int($this->userdata[$user_id]['__ci_vars'][$key]) && $keys[] = $key;
		}

		return $keys;
	}

	// ------------------------------------------------------------------------

	/**
	 * Unmark temp
	 *
	 * @param	mixed	$key	Session data key(s)
	 * @return	void
	 */
	public function unmark_temp($key, $user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		if (empty($this->userdata[$user_id]['__ci_vars']))
		{
			return;
		}

		is_array($key) OR $key = array($key);

		foreach ($key as $k)
		{
			if (isset($this->userdata[$user_id]['__ci_vars'][$k]) && is_int($this->userdata[$user_id]['__ci_vars'][$k]))
			{
				unset($this->userdata[$user_id]['__ci_vars'][$k]);
			}
		}

		if (empty($this->userdata[$user_id]['__ci_vars']))
		{
			unset($this->userdata[$user_id]['__ci_vars']);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * __get()
	 *
	 * @param	string	$key	'session_id' or a session data key
	 * @return	mixed
	 */
	public function __get($key)
	{
		// Note: Keep this order the same, just in case somebody wants to
		//       use 'session_id' as a session data key, for whatever reason
		if (isset($this->userdata[$this->default_user_id][$key]))
		{
			return $this->userdata[$this->default_user_id][$key];
		}

		return NULL;
	}

	// ------------------------------------------------------------------------

	/**
	 * __isset()
	 *
	 * @param	string	$key	'session_id' or a session data key
	 * @return	bool
	 */
	public function __isset($key)
	{
		return isset($this->userdata[$this->default_user_id][$key]);
	}

	// ------------------------------------------------------------------------

	/**
	 * __set()
	 *
	 * @param	string	$key	Session data key
	 * @param	mixed	$value	Session data value
	 * @return	void
	 */
	public function __set($key, $value)
	{
		$this->userdata[$this->default_user_id][$key] = $value;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get userdata reference
	 *
	 * Legacy CI_Session compatibility method
	 *
	 * @returns	array
	 */
	public function &get_userdata($user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		return $this->userdata[$user_id];
	}

	// ------------------------------------------------------------------------

	/**
	 * Userdata (fetch)
	 *
	 * Legacy CI_Session compatibility method
	 *
	 * @param	string	$key	Session data key
	 * @return	mixed	Session data value or NULL if not found
	 */
	public function userdata($key = NULL, $user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		if (isset($key))
		{
			return isset($this->userdata[$user_id][$key]) ? $this->userdata[$user_id][$key] : NULL;
		}
		elseif (empty($this->userdata[$user_id]))
		{
			return array();
		}

		$userdata = array();
		$_exclude = array_merge(
			array('__ci_vars'),
			$this->get_flash_keys($user_id),
			$this->get_temp_keys($user_id)
		);

		foreach (array_keys($this->userdata[$user_id]) as $key)
		{
			if ( ! in_array($key, $_exclude, TRUE))
			{
				$userdata[$key] = $this->userdata[$user_id][$key];
			}
		}

		return $userdata;
	}

	// ------------------------------------------------------------------------

	/**
	 * Set userdata
	 *
	 * Legacy CI_Session compatibility method
	 *
	 * @param	mixed	$data	Session data key or an associative array
	 * @param	mixed	$value	Value to store
	 * @return	void
	 */
	public function set_userdata($data, $value = NULL, $user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		if (is_array($data))
		{
			foreach ($data as $key => &$value)
			{
				$this->userdata[$user_id][$key] = $value;
			}

			return;
		}

		$this->userdata[$user_id][$data] = $value;
	}

	// ------------------------------------------------------------------------

	/**
	 * Unset userdata
	 *
	 * Legacy CI_Session compatibility method
	 *
	 * @param	mixed	$key	Session data key(s)
	 * @return	void
	 */
	public function unset_userdata($key, $user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		if (is_array($key))
		{
			foreach ($key as $k)
			{
				unset($this->userdata[$user_id][$k]);
			}

			return;
		}

		unset($this->userdata[$user_id][$key]);
	}

	// ------------------------------------------------------------------------

	/**
	 * All userdata (fetch)
	 *
	 * Legacy CI_Session compatibility method
	 *
	 * @return	array	$this->userdata, excluding flash data items
	 */
	public function all_userdata($user_id = NULL)
	{
		return $this->userdata($user_id);
	}

	// ------------------------------------------------------------------------

	/**
	 * Has userdata
	 *
	 * Legacy CI_Session compatibility method
	 *
	 * @param	string	$key	Session data key
	 * @return	bool
	 */
	public function has_userdata($key, $user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		return isset($this->userdata[$user_id][$key]);
	}

	// ------------------------------------------------------------------------

	/**
	 * Flashdata (fetch)
	 *
	 * Legacy CI_Session compatibility method
	 *
	 * @param	string	$key	Session data key
	 * @return	mixed	Session data value or NULL if not found
	 */
	public function flashdata($key = NULL, $user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		if (isset($key))
		{
			return (isset($this->userdata[$user_id]['__ci_vars'], $this->userdata[$user_id]['__ci_vars'][$key], $this->userdata[$user_id][$key]) && ! is_int($this->userdata[$user_id]['__ci_vars'][$key]))
				? $this->userdata[$user_id][$key]
				: NULL;
		}

		$flashdata = array();

		if ( ! empty($this->userdata[$user_id]['__ci_vars']))
		{
			foreach ($this->userdata[$user_id]['__ci_vars'] as $key => &$value)
			{
				is_int($value) OR $flashdata[$key] = $this->userdata[$user_id][$key];
			}
		}

		return $flashdata;
	}

	// ------------------------------------------------------------------------

	/**
	 * Set flashdata
	 *
	 * Legacy CI_Session compatibility method
	 *
	 * @param	mixed	$data	Session data key or an associative array
	 * @param	mixed	$value	Value to store
	 * @return	void
	 */
	public function set_flashdata($data, $value = NULL, $user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		$this->set_userdata($data, $value, $user_id);
		$this->mark_as_flash(is_array($data) ? array_keys($data) : $data, $user_id);
	}

	// ------------------------------------------------------------------------

	/**
	 * Keep flashdata
	 *
	 * Legacy CI_Session compatibility method
	 *
	 * @param	mixed	$key	Session data key(s)
	 * @return	void
	 */
	public function keep_flashdata($key, $user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		$this->mark_as_flash($key, $user_id);
	}

	// ------------------------------------------------------------------------

	/**
	 * Temp data (fetch)
	 *
	 * Legacy CI_Session compatibility method
	 *
	 * @param	string	$key	Session data key
	 * @return	mixed	Session data value or NULL if not found
	 */
	public function tempdata($key = NULL, $user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		if (isset($key))
		{
			return (isset($this->userdata[$user_id]['__ci_vars'], $this->userdata[$user_id]['__ci_vars'][$key], $this->userdata[$user_id][$key]) && is_int($this->userdata[$user_id]['__ci_vars'][$key]))
				? $this->userdata[$user_id][$key]
				: NULL;
		}

		$tempdata = array();

		if ( ! empty($this->userdata[$user_id]['__ci_vars']))
		{
			foreach ($this->userdata[$user_id]['__ci_vars'] as $key => &$value)
			{
				is_int($value) && $tempdata[$key] = $this->userdata[$user_id][$key];
			}
		}

		return $tempdata;
	}

	// ------------------------------------------------------------------------

	/**
	 * Set tempdata
	 *
	 * Legacy CI_Session compatibility method
	 *
	 * @param	mixed	$data	Session data key or an associative array of items
	 * @param	mixed	$value	Value to store
	 * @param	int	$ttl	Time-to-live in seconds
	 * @return	void
	 */
	public function set_tempdata($data, $value = NULL, $ttl = 300, $user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		$this->set_userdata($data, $value, $user_id);
		$this->mark_as_temp(is_array($data) ? array_keys($data) : $data, $ttl, $user_id);
	}

	// ------------------------------------------------------------------------

	/**
	 * Unset tempdata
	 *
	 * Legacy CI_Session compatibility method
	 *
	 * @param	mixed	$data	Session data key(s)
	 * @return	void
	 */
	public function unset_tempdata($key, $user_id = NULL)
	{
		if (empty($user_id))
		{
			$user_id = $this->default_user_id;
		}
	
		$this->unmark_temp($key, $user_id);
	}
	
}