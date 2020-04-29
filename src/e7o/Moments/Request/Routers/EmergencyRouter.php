<?php

namespace e7o\Moments\Request\Routers;

use \e7o\Moments\Moment;
use \e7o\Moments\Request\Request;
use \e7o\Moments\Response\Response;

/**
* This is an emergency router and will be used e.g. when we don't have a config, so also no
* routes to build etc.
*/
class EmergencyRouter implements Router
{
	public function __construct()
	{
	}
	
	public function callRoute(Moment $moment, Request $request, $route): Response
	{
		return new \e7o\Moments\Response\NullResponse();
	}
	
	public function callController(Moment $moment, Request $request): Response
	{
		return new \e7o\Moments\Response\NullResponse();
	}
	
	public function buildUrl(Request $request, string $route, array $params = [], bool $absolute = false): string
	{
		return $request->getBasePath();
	}
}
