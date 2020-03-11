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
 *		],
 *		...
 * ]
 */
class SimpleRouter implements Router
{
	const MATCH_PATTERN = '[^/]+';
	private $table;
	
	public function __construct(array &$routingTable)
	{
		$processed = [];
		foreach ($routingTable as $route) {
			$route['route'] = $this->unifyRoutingPath($route['route']);
			
			if (strpos($route['route'], '{') !== false) {
				$params = [];
				$route['_route_match_regex'] =
					'#'
					. preg_replace_callback(
						'#\{(' . static::MATCH_PATTERN . ')\}#',
						function ($match) use (&$params) {
							$paramName = $match[1];
							if ($paramName[0] == '*') {
								$paramName = substr($paramName, 1);
								$pattern = '.*';
							} else {
								$pattern = static::MATCH_PATTERN;
							}
							$params[$paramName] = 'null';
							return '(?<' . $paramName . '>' . $pattern . ')';
						},
						$route['route']
					)
					. '/?$#';
				$route['parameters'] = $params;
			} else {
				$route['parameters'] = [];
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
			throw new \Exception('This is a 404 :)');
		}
		
		return $this->callRoute($moment, $request, $route);
	}
	
	// todo: provide an easier way to call without request for momentcontroller
	public function buildUrl(Request $request, string $route, array $params = []): string
	{
		$route = $this->table[$route] ?? null;
		if (empty($route)) {
			throw new \Exception('Cannot build route: ' . $route);
		}
		$url = $request->getBasePath() . $route['route'];
		foreach ($params as $key => $value) {
			$url = str_replace(['{' . $key . '}', '{*' . $key . '}'], urlencode($value), $url);
			unset($params[$key]);
		}
		if (!empty($params)) {
			$url .= '?' . http_build_query($params);
		}
		return $url;
	}
	
	protected function unifyRoutingPath(string $path)
	{
		if (substr($path, 0, 10) == '/index.php') {
			$path = substr($path, 10);
		}
		
		if (strlen($path) == 0) {
			return '/';
		}
		
		if (substr($path, -1, 1) == '/' && strlen($path) > 1) {
			$path = substr($path, 0, -1);
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
