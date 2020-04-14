<?php

namespace e7o\Moments\Response;

class Response
{
	protected $content;
	protected $responseCode = null;
	
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
	
	public function setResponseCode(int $code)
	{
		$this->responseCode = $code;
	}
	
	public function render()
	{
		if (!empty($this->responseCode)) {
			http_response_code($this->responseCode);
		}
		header('Content-Type: text/html');
		if ($this->content instanceof \Closure) {
			$c = $this->content;
			$c();
		} else {
			echo $this->content;
		}
	}
}
