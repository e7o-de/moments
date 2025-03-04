<?php

namespace e7o\Moments\Request\Routers;

use \e7o\Moments\Moment;
use \e7o\Moments\Request\Request;
use \e7o\Moments\Response\Response;

/**
 * Very simple router matching out of a list of possible routes. It's possible
 * to define e.g. a 'method' key for further specification, the destination
 * controller can take care of that.
 * 
 * Example:
 * [
 *		[
 *			'route' => '/test/{parameter}/',
 *			'controller' => MyController::class,
 *			'method' => 'testAction',
 * 			'meta' => {
 * 				'title': 'My fancy test page',
 * 				'keywords': 'awesome,test'
 * 			},
 * 			'template': 'test.htm',
 *		],
 *		...
 * ]
 * 
 * It's possible to pass a regex for the parameter:
 * 
 * 'route': '/article/{id:[1-9][0-9]*}'
 */
class SimpleRouter implements Router
{
	const MATCH_PATTERN = '[^/?]+';
	private $table;
	
	public function __construct(array &$routingTable)
	{
		$processed = [];
		foreach ($routingTable as $route) {
			if (empty($route['route'])) {
				// Internal route, no URL available
				$route['route'] = null;
				$route['parameters'] = [];
			} else {
				$route['route'] = $this->unifyRoutingPath($route['route']);
				
				if (strpos($route['route'], '{') !== false) {
					$params = [];
					$route['_route_match_regex'] =
						'#'
						. preg_replace_callback(
							'#\{(' . static::MATCH_PATTERN . ')\}#',
							function ($match) use (&$params) {
								if (($p = strpos($match[1], ':')) !== false) {
									$pattern = substr($match[1], $p + 1);
									$paramName = substr($match[1], 0, $p);
								} else {
									$pattern = static::MATCH_PATTERN;
									$paramName = $match[1];
								}
								$params[$paramName] = 'null';
								return '(?<' . $paramName . '>' . $pattern . ')';
							},
							$route['route']
						)
						. '$#';
					$route['parameters'] = $params;
				} else {
					$route['parameters'] = [];
				}
			}
			$processed[$route['id']] = $route;
		}
		$this->table = $processed;
	}
	
	public function callRoute(Moment $moment, Request $request, $route): Response
	{
		if (is_string($route)) {
			$route = $this->table[$route];
		}
		
		$controller = $route['controller'];
		$instance = new $controller($moment);
		
		return $instance->handleRequest($request, $route);
	}
	
	public function callController(Moment $moment, Request $request): Response
	{
		$path = $this->unifyRoutingPath($request->getRoutingPath());
		$route = $this->findRoute($path);
		
		if (empty($route)) {
			if (isset($this->table['error-404'])) {
				$route = $this->table['error-404'];
			} else {
				throw new \Exception('This is a 404 :)');
			}
		}
		
		return $this->callRoute($moment, $request, $route);
	}
	
	public function getRoute($routeId): ?array
	{
		return $this->table[$routeId] ?? null;
	}
	
	// todo: provide an easier way to call without request for momentcontroller
	public function buildUrl(Request $request, string $route, array $params = [], bool $absolute = false): string
	{
		$route = $this->table[$route] ?? null;
		if (empty($route)) {
			throw new \Exception('Cannot build route: ' . $route);
		}
		$url = $request->getBasePath() . $route['route'];
		if (is_numeric(key($params))) {
			// Just replace by given order
			foreach ($params as $value) {
				$url = preg_replace(['/\{[^}]+\}/'], urlencode($value), $url, 1);
			}
			$params = [];
		} else {
			// Replace by param name
			foreach ($params as $key => $value) {
				$url = preg_replace(['/\{' . $key . '(:[^}]+)?\}/'], urlencode($value), $url);
				unset($params[$key]);
			}
		}
		if (!empty($params)) {
			$url .= '?' . http_build_query($params);
		}
		if ($absolute) {
			$url = $request->getProtocolPrefix() . $request->getHost() . $url;
		}
		return $url;
	}
	
	protected function unifyRoutingPath(string $path)
	{
		if (($p = strpos($path, '?')) !== false) {
			$path = substr($path, 0, $p);
		}
		
		if (substr($path, 0, 10) == '/index.php') {
			$path = substr($path, 10);
		}
		
		if (strlen($path) == 0) {
			return '/';
		}
		
		return $path;
	}
	
	protected function findRoute($path)
	{
		foreach ($this->table as $route) {
			if (!empty($route['_route_match_regex']) && preg_match_all($route['_route_match_regex'], $path, $matches)) {
				foreach ($matches as $key => $match) {
					if (isset($route['parameters'][$key])) {
						$route['parameters'][$key] = $match[0];
					}
				}
				
				return $route;
			} else if ($route['route'] === $path) {
				return $route;
			}
		}
		
		return null;
	}
}
