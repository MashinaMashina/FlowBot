<?php

class FB_JSON_Constructor {
	
	public function __call($name, $arguments)
	{
		if (isset($this->$name) and is_object($this->$name))
		{
			return $this->$name;
		}
		
		$this->$name = (count($arguments) > 1) ? $arguments : $arguments[0];
		
		return $this;
	}
	
	public function __toString()
	{
		return json_encode($this);
	}
}