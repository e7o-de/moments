<?php

namespace e7o\Moments\Request;

class Request
{
	protected $body;
	protected $routingPath;
	protected $basePath;
	
	public function __construct()
	{
		$this->body = file_get_contents('php://input');
		$this->buildPaths();
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
	
	private function buildPaths()
	{
		if (isset($_SERVER['PATH_INFO'])) {
			// TODO: This might not work :)
			$this->routingPath = $this->removeParams($_SERVER['PATH_INFO']);
			$fullPath = $_SERVER['PATH_INFO'];
		} else if (isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SCRIPT_NAME'])) {
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
		return $_REQUEST;
	}
	
	public function getParameter($parameter)
	{
		return $_REQUEST[$parameter] ?? null;
	}
}
