<?php

namespace e7o\Moments;

use \e7o\Moments\Bundles\BundleManager;
use \e7o\Moments\Configuration\AlternativeReader;
use \e7o\Moments\Configuration\JsonReader;

class Moment
{
	protected $config;
	protected $router;
	protected $services;
	protected $baseDir;
	protected $momentsBaseDir;
	protected $bundleManager;
	
	private $serviceCache = [];
	
	public function __construct(string $baseDir)
	{
		$this->momentsBaseDir = realpath(__DIR__ . '/../../../');
		$baseDir = realpath($baseDir);
		if ($baseDir[-1] == '/') {
			$this->baseDir = substr($baseDir, 0, -1);
		} else {
			$this->baseDir = $baseDir;
		}
		try {
			$this->bundleManager = new BundleManager($this->baseDir . '/config/bundles-generated.json');
			$this->config = new AlternativeReader(
				new JsonReader($this->baseDir . '/config/config.json'),
				new JsonReader($this->baseDir . '/config/credentials.json'),
				new JsonReader(__DIR__ . '/../../../config/default.json'),
				$this->bundleManager
			);
		} catch (\Exception $e) {
			$this->outputEmergencyError($e);
		}
		$routerClass = $this->config->getAll('router')['class'];
		$routes = $this->config->getAll('routes');
		
		$this->router = new $routerClass($routes);
		
		$this->initServices();
	}
	
	public function takePlace($request = null)
	{
		try {
			if ($request === null) {
				$request = new \e7o\Moments\Request\Request($this->config->get('baseurl'));
			}
			
			$this->setService('request', $request);
			
			$response = $this->router->callController($this, $request);
			$response->render();
		} catch (\Exception $e) {
			$this->outputEmergencyError($e);
		}
	}
	
	public function getEnvironment(): string
	{
		// todo: validate ;)
		return $this->config->get('environment', 'prod');
	}
	
	public function getService(string $serviceName, array $params = [])
	{
		// Cached?
		$cacheName = $serviceName;
		if (!empty($params)) {
			$cacheName .= '__' . serialize($params);
		}
		if (!empty($this->serviceCache[$cacheName])) {
			return $this->serviceCache[$cacheName];
		}
		
		// Normal instantiation
		if (!empty($this->services[$serviceName])) {
			$service = $this->services[$serviceName];
			if (is_object($service)) {
				return $service;
			}
			$args = $this->assembleArgs($service['args'] ?? [], $params);
			if (isset($service['factory'])) {
				$instance = call_user_func_array($service['factory'] . '::get', $args);
			} else if (isset($service['class'])) {
				$class = new \ReflectionClass($service['class']);
				$instance = $class->newInstanceArgs($args);
			} else {
				// TODO: Exception
				return null;
			}
			
			$this->serviceCache[$serviceName] = $instance;
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
		$services = [
			'moment' => $this,
			'router' => $this->router,
			'config' => $this->config,
			'bundles' => $this->bundleManager,
		];
		$services += $this->config->getAll('services');
		$this->services = $services;
	}
	
	public function setService($name, $obj)
	{
		unset($this->serviceCache[$name]);
		$this->services[$name] = $obj;
	}
	
	public function callEvents(string $event, ...$args)
	{
		$all = $this->config->getAll('events')[$event] ?? [];
		foreach ($all as $event) {
			list($class, $method) = explode('::', $event, 2);
			$args = $class::$method($this, ...$args);
			if (!is_array($args)) {
				$args = [$args];
			}
		}
		return $args;
	}
	
	private function assembleArgs(array $args, array $params = [])
	{
		$collectedArgs = [];
		foreach ($args as $key => $arg) {
			if (is_array($arg)) {
				$collectedArgs[$key] = $this->assembleArgs($arg);
			} else if ($arg[0] == '%') { // TODO: Escaping
				$arg = $this->config->get(substr($arg, 1));
				$this->preprocessArgs($arg, $params);
				$collectedArgs[$key] = $arg;
			} else if ($arg[0] == '@') {
				// ToDo: Check for recursions etc.
				$collectedArgs[$key] = $this->getService(substr($arg, 1));
			} else {
				$this->preprocessArgs($arg, $params);
				$collectedArgs[$key] = $arg;
			}
		}
		return $collectedArgs;
	}
	
	private function preprocessArgs(&$arg, &$params)
	{
		if (is_array($arg)) {
			foreach ($arg as &$v) {
				$this->preprocessArgs($v, $params);
			}
		} else if (is_string($arg)) {
			foreach ($params as $param => $value) {
				$arg = str_replace('${' . $param . '}', $value, $arg);
			}
			// Very ugly solution, but works for only few vars ;)
			$arg = str_replace('${root}', $this->baseDir, $arg);
			$arg = str_replace('${moments}', $this->momentsBaseDir, $arg);
			if (($c = strpos($arg, '${')) !== false) {
				throw new \Exception('Missing parameter in injector args (starting at #' . $c . ')');
			}
		}
	}
	
	/**
	* Emergency error message, which is shown, when the rendering infrastructure
	* is not fully initialised (like on config file errors) and therefore there's
	* no regular MomentsController nor routes or so. It can somehow render with
	* style, but e.g. bundle css/scripts are missing.
	*/
	private function outputEmergencyError(\Exception $e)
	{
		$this->services['request'] = new \e7o\Moments\Request\Request();
		$this->services['router'] = new \e7o\Moments\Request\Routers\EmergencyRouter();
		$loader = new \e7o\Moments\Output\Template\MomentsLoader(
			$this->baseDir . '/views',
			$this->momentsBaseDir . '/views'
		);
		$this->services['template'] = new \e7o\Morosity\Morosity($loader);
		$this->services['controller'] = new Request\Controllers\MomentsController($this);
		\e7o\Moments\Output\Template\Functions::add($this->services['template'], $this->services['controller']);
		$output = $this->services['template']->render(
			'error_500.htm',
			[
				'message' => $e->getMessage(),
				'code' => $e->getCode(),
				'line' => $e->getLine(),
				'file' => $e->getFile(),
				'trace' => $e->getTrace(),
				'assets' => $this->services['request']->getBasePath() . '/assets/',
				'top' => $this->services['request']->getBasePath() . '/',
			]
		);
		$response = new Response\Response($output);
		$response->render();
		die();
	}
}
