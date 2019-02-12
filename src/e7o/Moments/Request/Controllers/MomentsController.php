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
	
	public function __construct(Moment $moment)
	{
		$this->moment = $moment;
		$this->template = $moment->getService('template');
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
		
		try {
			if (isset($route['require'])) {
				// todo: refactor out
				foreach ($route['require'] as $type => $value) {
					switch ($type) {
						case 'user-group':
							if (!$this->moment->getService('user_provider')->hasRights($value)) {
								// todo: error handling if not existing
								return $this->fallback($request, $this->moment->getService('config')->get('router', [])['on-unauthorized']);
							}
							break;
					}
				}
			}
			
			$args = [];
			$method = new \ReflectionMethod(static::class, $method);
			
			foreach ($method->getParameters() as $name) {
				if (!isset($route['parameters'][$name->getName()])) {
					throw new \Exception('Parameter ' . $name->getName() . ' unknown, maybe a typo');
				}
				
				$args[] = $route['parameters'][$name->getName()];
			}
			
			$returned = $method->invokeArgs($this, $args);
			
			if (!empty($route['template'])) {
				// todo: make real object with url builder, lazy evaluation etc.
				$returned['$'] = [
					'assets' => $this->request->getBasePath() . '/assets/',
					'top' => $this->request->getBasePath() . '/',
				];
				$returned = new Response($this->template->render($route['template'], $returned));
			} else if (!empty($route['json'])) {
				$returned = new JsonResponse($returned);
			} else if (!($returned instanceof Response)) {
				$returned = new Response($returned);
			}
			
			return $returned;
		} catch (\Exception $e) {
			// TODO
			if ($this->moment->getEnvironment() == 'dev') {
				$t = '<p>Moments catched a controller exception:</p><h1>' .  $e->getMessage() . '</h1><pre>' . $e->getTraceAsString() . '</pre>';
			} else {
				$ref = md5(microtime() . rand(1, 100000));
				// todo: nicer location or mail or so ;)
				file_put_contents('/tmp/moments_' . $ref, $e->getMessage() . PHP_EOL . $e->getTraceAsString());
				$t = '<p>Moments catched an exception with reference ' . $ref . ' (please mention that number when complaining)</p>';
			}
			return new Response($t);
		}
	}
	
	public function getMoment(): Moment
	{
		return $this->moment;
	}
	
	protected function fallback($request, $rule)
	{
		if (isset($rule['delegate'])) {
			return $this
				->moment
				->getService('router')
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
	
	protected function get(string $service)
	{
		return $this->moment->getService($service);
	}
}
