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
		if (($_SERVER['CONTENT_TYPE'] ?? null) == 'application/json' && strlen($this->body) > 0) {
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
		if (isset($_SERVER['DOCUMENT_URI'])) {
			// e.g. nginx
			// 17 == len(/public/index.php)
			$this->basePath = substr($_SERVER['DOCUMENT_URI'], 0, -17);
			$requestUri = $_SERVER['REQUEST_URI'];
		} else if (isset($_SERVER['SCRIPT_URL']) && isset($_SERVER['DOCUMENT_ROOT'])) {
			// e.g. Apache
			$this->basePath = substr($_SERVER['SCRIPT_FILENAME'], 0, -17);
			$this->basePath = substr($this->basePath, strlen($_SERVER['DOCUMENT_ROOT']));
			$requestUri = $_SERVER['SCRIPT_URL'];
		} else if (isset($_SERVER['PHP_SELF'])) {
			// e.g. PHP dev server
			$p = explode('public/index.php', $_SERVER['PHP_SELF'], 2);
			$this->basePath = $p[0] . 'public/';
			$requestUri = null;
			$this->routingPath = $p[1];
		} else {
			// We have no chance in this case. This happens, if e.g. the fastcgi variables in nginx
			// are not configured properly or so.
			throw new \Exception('Unsupported server or missconfigured variables (requires DOCUMENT_URI and REQUEST_URI)');
		}
		if ($this->basePath[-1] == '/') {
			$this->basePath = substr($this->basePath, 0, -1);
		}
		if ($requestUri !== null) {
			$this->routingPath = substr($requestUri, strlen($this->basePath));
		}
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
	
	public function offsetExists($parameter): bool
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
