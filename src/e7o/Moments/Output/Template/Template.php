<?php

namespace e7o\Moments\Output\Template;

/**
 * Stupid template dummy, only doing very simple replacements.
 */
class Template
{
	private $loader;
	
	public function __construct($loader)
	{
		$this->loader = $loader;
	}
	
	public function render(string $file, array $params = [])
	{
		// Very very basic implementation, even whitespaces are important ;)
		$template = $this->loader->load($file);
		return $this->renderString($template, $params);
	}
	
	public function renderString(string $template, array $params = [])
	{
		foreach ($params as $key => $value) {
			$template = str_replace('{{ ' . $key . ' }}', $value, $template);
		}
		return $template;
	}
}
