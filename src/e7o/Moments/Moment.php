<?php

namespace e7o\Moments;

use \e7o\Moments\Configuration\AlternativeReader;
use \e7o\Moments\Configuration\JsonReader;

class Moment
{
	protected $config;
	protected $router;
	protected $services;
	protected $baseDir;
	
	public function __construct(string $baseDir)
	{
		$baseDir = realpath($baseDir);
		if (substr($baseDir, -1) == '/') {
			$this->baseDir = substr($baseDir, 0, -1);
		} else {
			$this->baseDir = $baseDir;
		}
		$defaultConfig = new JsonReader(__DIR__ . '/../../../config/default.json');
		$customConfig = new JsonReader($this->baseDir . '/config/config.json');
		$credentialsConfig = new JsonReader($this->baseDir . '/config/credentials.json');
		$this->config = new AlternativeReader($customConfig, $credentialsConfig, $defaultConfig);
		
		$routerClass = $this->config->getAll('router')['class'];
		$routes = $this->config->getAll('routes');
		$this->router = new $routerClass($routes);
		
		$this->initServices();
	}
	
	public function takePlace($request = null)
	{
		if ($request === null) {
			$request = new \e7o\Moments\Request\Request();
		}
		
		$response = $this->router->callController($this, $request);
		$response->render();
	}
	
	public function getEnvironment(): string
	{
		// todo: validate ;)
		return $this->config->get('environment', 'prod');
	}
	
	public function getService(string $serviceName)
	{
		// TODO: Move out to own class
		static $cache = [];
		
		// Workaround until we have explicit container stuff
		switch ($serviceName) {
			case 'config';
				return $this->config;
		}
		
		// Cache?
		if (isset($cache[$serviceName])) {
			return $cache[$serviceName];
		}
		
		// Normal instantiation
		if (isset($this->services[$serviceName])) {
			$service = $this->services[$serviceName];
			if (is_object($service)) {
				return $service;
			}
			$args = $this->assembleArgs($service['args'] ?? []);
			if (isset($service['factory'])) {
				$instance = call_user_func_array($service['factory'] . '::get', $args);
			} else if (isset($service['class'])) {
				$class = new \ReflectionClass($service['class']);
				$instance = $class->newInstanceArgs($args);
			} else {
				// TODO: Exception
				return null;
			}
			
			$cache[$serviceName] = $instance;
			return $instance;
		}
		
		// TODO: Exception
		return null;
	}
	
	public function getBasePath()
	{
		return $this->baseDir;
	}
	
	private function initServices()
	{
		$services = $this->config->getAll('services');
		$services += [
			'router' => $this->router,
		];
		$this->services = $services;
	}
	
	private function assembleArgs(array $args)
	{
		$collectedArgs = [];
		foreach ($args as $key => $arg) {
			if (is_array($arg)) {
				$collectedArgs[$key] = $this->assembleArgs($arg);
			} else if ($arg[0] == '%') { // TODO: Escaping
				$arg = $this->config->get(substr($arg, 1));
				$this->preprocessArgs($arg);
				$collectedArgs[$key] = $arg;
			} else if ($arg[0] == '@') {
				// ToDo: Check for recursions etc.
				$collectedArgs[$key] = $this->getService(substr($arg, 1));
			} else {
				$this->preprocessArgs($arg);
				$collectedArgs[$key] = $arg;
			}
		}
		return $collectedArgs;
	}
	
	private function preprocessArgs(&$arg)
	{
		if (is_array($arg)) {
			foreach ($arg as &$v) {
				$this->preprocessArgs($v);
			}
		} else if (is_string($arg)) {
			// Very ugly solution, but works for only few vars ;)
			$arg = str_replace('${root}', $this->baseDir, $arg);
		}
	}
}
