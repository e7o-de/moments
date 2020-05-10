<?php

namespace e7o\Moments\Configuration;

class AlternativeReader implements Configuration
{
	private $configs;
	
	public function __construct(Configuration... $configs)
	{
		$this->configs = $configs;
	}
	
	public function addConfiguration(Configuration $config)
	{
		$this->configs[] = $config;
	}
	
	public function get(string $name, $default = null)
	{
		foreach ($this->configs as $config) {
			$value = $config->get($name, null);
			if ($value !== null) {
				return $value;
			}
		}
		
		return $default;
	}
	
	public function getAll(string $name): array
	{
		$values = [];
		foreach ($this->configs as $config) {
			$value = $config->get($name, null);
			if ($value !== null) {
				$values = array_merge_recursive($values, $value);
			}
		}
		
		return $values;
	}
}

