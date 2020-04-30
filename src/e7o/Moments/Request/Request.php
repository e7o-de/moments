<?php

namespace e7o\Moments\Request;

class Request implements \ArrayAccess
{
	protected $body;
	protected $routingPath;
	protected $basePath;
	protected $params;
	
	public function __construct()
	{
		$this->body = file_get_contents('php://input');
		$this->buildPaths();
		$this->params = $_REQUEST + $_FILES + $_COOKIE;
		if (($p = strpos($_SERVER['REQUEST_URI'], '?')) !== false) {
			$urlparams = [];
			parse_str(substr($_SERVER['REQUEST_URI'], $p + 1), $urlparams);
			$this->params += $urlparams;
		}
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
	
	public function getUrl(): string
	{
		return $_SERVER['REQUEST_URI'];
	}
	
	public function getRoutingPath(): string
	{
		return $this->routingPath;
	}
	
	public function getBasePath(): string
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
	
	private function removeParams(string $uri): string
	{
		$p = strpos($uri, '?');
		if ($p !== false) {
			$uri = substr($uri, 0, $p);
		}
		return $uri;
	}
	
	public function getHost(): string
	{
		return $_SERVER['HTTP_HOST'];
	}
	
	public function getProtocolPrefix(): string
	{
		return ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http') . '://';
	}
	
	public function getBody()
	{
		return $this->body;
	}
	
	public function getParameters(): array
	{
		return $this->params;
	}
	
	public function getParameter(string $parameter, $default = null)
	{
		return $this->params[$parameter] ?? $default;
	}
	
	public function offsetExists($offset): bool
	{
		return isset($this->params[$parameter]);
	}
	
	public function offsetGet($parameter)
	{
		return $this->getParameter($parameter);
	}
	
	public function offsetSet($parameter, $value)
	{
		$this->params[$parameter] = $value;
	}
	
	public function offsetUnset($parameter)
	{
		unset($this->params[$parameter]);
	}
}
