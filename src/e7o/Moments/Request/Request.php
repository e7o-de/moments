<?php

namespace e7o\Moments\Request;

class Request
{
	protected $body;
	protected $routingPath;
	protected $basePath;
	protected $params;
	
	public function __construct()
	{
		$this->body = file_get_contents('php://input');
		$this->buildPaths();
		$this->params = $_REQUEST + $_FILES;
		if ($_SERVER['CONTENT_TYPE'] == 'application/json' && strlen($this->body) > 0) {
			$dec = json_decode($this->body, true);
			if (strtolower($this->body) === 'null') {
				// Just in case ;)
			} else if (is_array($dec)) {
				$this->params += $dec;
			} else {
				throw new \Exception('This is a 400 Bad Request - JSON error: ' . \json_last_error_msg());
			}
		}
	}
	
	public function getUrl()
	{
		return $_SERVER['REQUEST_URI'];
	}
	
	public function getRoutingPath()
	{
		return $this->routingPath;
	}
	
	public function getBasePath()
	{
		return $this->basePath;
	}
	
	/*
	*	Works with this one:
	*	
	*	location /project/ {
	*		try_files $uri /project/public/index.php;
	*	}
	*/
	private function buildPaths()
	{
		// 17 == len(/public/index.php)
		$this->basePath = substr($_SERVER['DOCUMENT_URI'], 0, -17);
		if ($this->basePath[-1] == '/') {
			$this->basePath = substr($this->basePath, 0, -1);
		}
		$this->routingPath = substr($_SERVER['REQUEST_URI'], strlen($this->basePath));
		if (strlen($this->routingPath) == 0) {
			$this->routingPath = '/';
		}
	}
	
	private function removeParams($uri)
	{
		$p = strpos($uri, '?');
		if ($p !== false) {
			$uri = substr($uri, 0, $p);
		}
		return $uri;
	}
	
	public function getHost()
	{
		return $_SERVER['HTTP_HOST'];
	}
	
	public function getBody()
	{
		return $this->body;
	}
	
	public function getParameters()
	{
		return $this->params;
	}
	
	public function getParameter($parameter, $default = null)
	{
		return $this->params[$parameter] ?? $default;
	}
}

