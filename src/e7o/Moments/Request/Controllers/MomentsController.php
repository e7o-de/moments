<?php

namespace e7o\Moments\Request\Controllers;

use \e7o\Moments\Moment;
use \e7o\Moments\Request\Request;
use \e7o\Moments\Response\Response;
use \e7o\Moments\Response\JsonResponse;
use \e7o\Moments\Request\Routers\Router;

class MomentsController implements Controller
{
	private $request;
	private $moment;
	private $template;
	private $route;
	private $metaCollected = [];
	
	public function __construct(Moment $moment)
	{
		$this->moment = $moment;
		$moment->setService('controller', $this);
		$this->template = $this->get('template');
	}
	
	public function handleRequest(Request $request, $route): Response
	{
		if (!isset($route['method'])) {
			throw new \Exception('Cannot route without method given');
		}
		
		$method = $route['method'];
		if (!is_callable([$this, $method])) {
			throw new \Exception('Cannot call method defined by route');
		}
		
		$this->request = $request;
		$this->route = $route;
		
		try {
			if (isset($route['require'])) {
				// todo: refactor out
				foreach ($route['require'] as $type => $value) {
					switch ($type) {
						case 'user-group':
							if (!$this->get('user_provider')->hasRights($value)) {
								// todo: error handling if not existing
								return $this->fallback($request, $this->get('config')->get('router', [])['on-unauthorized']);
							}
							break;
					}
				}
			}
			
			$args = [];
			$method = new \ReflectionMethod(static::class, $method);
			
			foreach ($method->getParameters() as $name) {
				if (!isset($route['parameters'][$name->getName()])) {
					if ($name->isOptional()) {
						$args[] = $name->getDefaultValue();
					} else {
						throw new \Exception('Parameter ' . $name->getName() . ' unknown, maybe a typo');
					}
				} else {
					$args[] = $route['parameters'][$name->getName()];
				}
			}
			
			$returned = $method->invokeArgs($this, $args);
			
			if (!empty($route['template'])) {
				// todo: make real object with url builder, lazy evaluation etc.
				$returned['$'] = $this->getTemplateVars();
				$returned = new Response($this->template->render($route['template'], $returned));
			} else if (!empty($route['json'])) {
				$returned = new JsonResponse($returned);
			} else if (!($returned instanceof Response)) {
				$returned = new Response($returned);
			}
			
			return $returned;
		} catch (\Throwable $e) {
			// TODO
			if ($this->moment->getEnvironment() == 'dev') {
				$t = '<p>Moments catched an error in a controller:</p><h1>' .  $e->getMessage() . '</h1><pre>' . $e->getTraceAsString() . '</pre>';
			} else {
				$errhandler = $this->get('config')->get('error');
				if (isset($errhandler['template'])) {
					$a = [
						'message' => $e->getMessage(),
						'code' => $e->getCode(),
						'line' => $e->getLine(),
						'file' => $e->getFile(),
						'trace' => $e->getTrace(),
					];
					$t = $this->template->render($errhandler['template'], $a);
				} else {
					$ref = md5(microtime() . rand(1, 100000));
					// todo: nicer location or mail or so ;)
					file_put_contents('/tmp/moments_' . $ref, $e->getMessage() . PHP_EOL . $e->getTraceAsString());
					$t = '<p>Moments catched an exception with reference ' . $ref . ' (please mention that number when complaining)</p>';
				}
			}
			return new Response($t);
		}
	}
	
	public function getMoment(): Moment
	{
		return $this->moment;
	}
	
	protected function getTemplateVars()
	{
		return [
			'assets' => $this->request->getBasePath() . '/assets/',
			'top' => $this->request->getBasePath() . '/',
			'meta' => implode(PHP_EOL, $this->getHeadHtml()),
		];
	}
	
	public function addMetaTag($name, $value)
	{
		$this->metaCollected[] = '<meta name="' . $name . '" content="' . htmlentities($value, null, 'UTF-8') . '" />';
	}
	
	public function addScript($file)
	{
		$this->metaCollected[] = '<script src="' . $this->request->getBasePath() . '/assets/' . $file . '" type="text/javascript"></script>';
	}
	
	public function addStylesheet($file)
	{
		$this->metaCollected[] = '<link rel="stylesheet" type="text/css" href="' . $this->request->getBasePath() . '/assets/' . $file . '" />';
	}
	
	private function getHeadHtml()
	{
		$this->get('bundles')->addRequiredAssets($this);
		return $this->metaCollected;
	}
	
	protected function fallback($request, $rule)
	{
		if (isset($rule['delegate'])) {
			return $this
				->get('router')
				->callRoute($this->moment, $request, $rule['delegate'])
			;
		} else {
			throw new \Exception('Unauthenticated');
		}
	}
	
	protected function getRequest(): Request
	{
		return $this->request;
	}
	
	protected function getRouter(): Router
	{
		return $this->get('router');
	}
	
	protected function getRoute(): array
	{
		return $this->route ?? [];
	}
	
	protected function getParameters(): array
	{
		return $this->getRoute()['parameters'] ?? [];
	}
	
	/**
	* Rebuilds the current route with new parameters.
	*/
	protected function rebuildRoute(array $params = [], bool $absolute = false)
	{
		return $this->get('router')->buildUrl($this->getRequest(), $this->route['id'], $params, $absolute);
	}
	
	protected function get(string $service)
	{
		return $this->moment->getService($service);
	}
}
