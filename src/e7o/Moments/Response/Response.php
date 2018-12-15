<?php

namespace e7o\Moments\Response;

class Response
{
	protected $content;
	
	public function __construct($content)
	{
		$this->content = $content;
	}
	
	public function addCookie($name, $value, $expiration = -1)
	{
		// todo: only on render ;)
		if ($expiration == -1) {
			$expiration = time() + 86400 * 365;
		}
		setcookie($name, $value, $expiration, '/');
	}
	
	public function render()
	{
		header('Content-Type: text/html');
		echo $this->content;
	}
}
