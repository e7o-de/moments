<?php

namespace e7o\Moments\Configuration;

interface Configuration
{
	public function get(string $name, $default = null);
}

