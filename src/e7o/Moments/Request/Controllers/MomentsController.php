<?php

namespace e7o\Moments\Request\Controllers;

use \e7o\Moments\Moment;
use \e7o\Moments\Request\Request;
use \e7o\Moments\Response\Response;
use \e7o\Moments\Response\JsonResponse;
use \e7o\Moments\Response\RedirectResponse;
use \e7o\Moments\Response\NullResponse;
use \e7o\Moments\Request\Routers\Router;
use \e7o\Moments\Helper\Authenticator;

use \e7o\Morosity\Executor\Handler;
use \e7o\Morosity\Executor\VariableContext;

class MomentsController implements Controller
{
	private $request;
	private $moment;
	private $template = null;
	private $route;
	private $metaCollected = [];
	private $authenticator = null;
	
	public function __construct(Moment $moment)
	{
		$this->moment = $moment;
		$moment->setService('controller', $this);
	}
	
	public function handleRequest(Request $request, $route): Response
	{
		try {
			$this->request = $request;
			$this->route = $route;
			
			$this->authenticator = $this->get('authenticator');
			if ($this->authenticator !== null) {
				$this->authenticator->checkLogin($request, $route);
			}
			
			$allowed = $this->isAllowed();
			if ($allowed instanceof Response) {
				// Awesome, we've got a result already!
				return $allowed;
			} else if (is_string($allowed)) {
				$this->route = $this->getRouter()->getRoute($allowed);
			} else if ($allowed !== true) {
				$this->handleError(new \Exception('Access denied'));
			}
			
			if ($this->authenticator !== null) {
				$allowed = $this->authenticator->isAllowed($request, $route);
				if ($allowed !== true) {
					$url = $this->buildRoute(
						$this->authenticator->getAuthenticationRoute(),
						[],
						true
					) . '?rd=' . urlencode($request->getRoutingPath());
					return new RedirectResponse($url);
				}
			}
			
			// Changed routes?
			if ($this->route['controller'] !== $route['controller']) {
				// New controller responsibility!
				return $this->getRouter()->callRoute($this->moment, $request, $this->route);
			}
			$route = $this->route;
			
			if (!isset($route['method'])) {
				throw new \Exception('Cannot route without method given');
			}
			
			$method = $route['method'];
			if (!is_callable([$this, $method])) {
				throw new \Exception('Cannot call method defined by route');
			}
			
			if (!empty($route['template'])) {
				$this->initTemplate();
			}
			
			$args = [];
			$method = new \ReflectionMethod(static::class, $method);
			
			foreach ($method->getParameters() as $name) {
				if (!isset($route['parameters'][$name->getName()])) {
					if ($name->isOptional()) {
						$args[] = $name->getDefaultValue();
					} else {
						throw new \Exception(
							'Parameter ' . $name->getName() . ' for route ' . $route['id'] . ' unknown, maybe a typo'
						);
					}
				} else {
					$args[] = $route['parameters'][$name->getName()];
				}
			}
			
			$returned = $method->invokeArgs($this, $args);
			$this->moment->callEvents('controller:finished', $this, $returned);
			
			if (!empty($route['meta'])) {
				$this->handleMeta($route['meta']);
			}
			
			if ($returned instanceof Response) {
				// Skip, we have a Response object already
			} else if (!empty($route['template'])) {
				// todo: make real object with url builder, lazy evaluation etc.
				$returned['$'] = $this->getTemplateVars();
				$html = $this->template->render($route['template'], $returned);
				$html = $this->moment->callEvents('output:html', $html)[0];
				$returned = new Response($html);
			} else if (!empty($route['json'])) {
				$returned = new JsonResponse($returned);
			} else if ($returned === null) {
				$returned = new NullResponse;
			} else if (!($returned instanceof Response)) {
				$returned = new Response($returned);
			}
			
			if (!empty($route['responsecode'])) {
				$returned->setResponseCode((int)$route['responsecode']);
			}
			
			return $returned;
		} catch (\Throwable $e) {
			return $this->handleError($e);
		}
	}
	
	protected function handleError(\Throwable $e): Response
	{
		// TODO
		if ($this->moment->getEnvironment() == 'dev') {
			$t = '<p>Moments catched an error in a controller:</p><h1>' .  $e->getMessage() . '</h1><pre>' . $e->getTraceAsString() . '</pre>';
		} else {
			// TODO: use a route like with 404
			$errhandler = $this->get('config')->get('error');
			if (isset($errhandler['template'])) {
				$a = $this->getTemplateVars() + [
					'message' => $e->getMessage(),
					'code' => $e->getCode(),
					'line' => $e->getLine(),
					'file' => $e->getFile(),
					'trace' => $e->getTrace(),
				];
				$this->initTemplate();
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
	
	public function isAllowed()
	{
		return true;
	}
	
	public function getMoment(): Moment
	{
		return $this->moment;
	}
	
	public function getAuthenticator(): Authenticator
	{
		// Just fill the authenticator field with our dummy Authenticator,
		// so controllers won't break on a null value. At the point controllers
		// are run, our internal authentication procedure is done, so we can
		// polute this variable.
		if ($this->authenticator == null) {
			$this->authenticator = new Authenticator($this->get('database'));
		}
		return $this->authenticator;
	}
	
	protected function handleMeta($meta)
	{
		if (isset($meta['title'])) {
			$this->setTitle($meta['title']);
			unset($meta['title']);
		}
		foreach ($meta as $key => $value) {
			$this->addMetaTag($key, $value);
		}
	}
	
	protected function getTemplateVars()
	{
		$route = $this->route;
		$route['requesturi'] = $this->request->getUrl();
		return [
			'assets' => $this->request->getBasePath() . '/assets/',
			'top' => $this->request->getBasePath() . '/',
			'meta' => implode(PHP_EOL, $this->getHeadHtml()),
			'user' => $this->authenticator ? $this->authenticator->getUserForTemplate() : null,
			'route' => $route,
		];
	}
	
	public function setTitle($title)
	{
		$this->metaCollected['title'] = '<title>' . htmlentities($title, null, 'UTF-8') . '</title>';
	}
	
	public function addMetaTag($name, $value)
	{
		$this->metaCollected[] = '<meta name="' . $name . '" content="' . htmlentities($value, null, 'UTF-8') . '" />';
	}
	
	public function addScript($file)
	{
		$this->metaCollected[md5($file)] = '<script src="' . $this->request->getBasePath() . '/assets/' . $file . '" type="text/javascript"></script>';
	}
	
	public function addStylesheet($file)
	{
		$this->metaCollected[md5($file)] = '<link rel="stylesheet" type="text/css" href="' . $this->request->getBasePath() . '/assets/' . $file . '" />';
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
	
	public function getRequest(): Request
	{
		return $this->request ?? $this->get('request');
	}
	
	public function getRouter(): Router
	{
		return $this->get('router');
	}
	
	public function getRoute(): array
	{
		return $this->route ?? [];
	}
	
	public function getParameters(): array
	{
		return $this->getRoute()['parameters'] ?? [];
	}
	
	/**
	* Rebuilds the current route with new parameters.
	*/
	public function rebuildRoute(array $params = [], bool $absolute = false)
	{
		return $this->buildRoute($this->route['id'], $params, $absolute);
	}
	
	public function buildRoute($routeId, array $params = [], bool $absolute = false)
	{
		return $this->get('router')->buildUrl($this->getRequest(), $routeId, $params, $absolute);
	}
	
	public function get(string $service)
	{
		return $this->moment->getService($service);
	}
	
	/**
	* Prepares the template renderer. Adds some magic, so templates can create routes etc.
	*/
	private function initTemplate()
	{
		if (!empty($this->template)) {
			return;
		}
		
		$this->template = $this->get('template');
		\e7o\Moments\Output\Template\Functions::add($this->template, $this);
	}
}
