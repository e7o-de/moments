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
		$this->params = $_REQUEST;
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
	*	location /project/public {
	*		try_files $uri /project/public/index.php;
	*	}
	*/
	private function buildPaths()
	{
		if (isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SCRIPT_NAME'])) {
			$path = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
			if (substr($_SERVER['REQUEST_URI'], 0, strlen($path)) == $path) {
				$this->routingPath = $this->removeParams(substr($_SERVER['REQUEST_URI'], strlen($path)));
				$fullPath = $_SERVER['REQUEST_URI'];
			}
		} else {
			// TODO: Shouldn't happen, log something
			$this->routingPath = '';
		}
		$this->basePath = substr($fullPath, 0, -strlen($this->routingPath));
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
	
	public function getParameter($parameter)
	{
		return $this->params[$parameter] ?? null;
	}
}

