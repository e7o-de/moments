<?php

namespace e7o\Moments\Configuration;

class JsonReader implements Configuration
{
	private $config;
	
	public function __construct(string $configFile)
	{
		if (file_exists($configFile)) {
			$this->config = json_decode(file_get_contents($configFile), true);
			if ($this->config == null && json_last_error() != JSON_ERROR_NONE) {
				throw new \Exception('Cannot parse ' . $configFile . ': ' . json_last_error_msg());
			}
		} else {
			$this->config = [];
		}
	}
	
	public function get(string $name, $default = null)
	{
		$name = explode('.', $name);
		$next = &$this->config;
		
		foreach ($name as $step) {
			if (isset($next[$step])) {
				$next = &$next[$step];
			} else {
				return $default;
			}
		}
		
		return $next;
	}
}

