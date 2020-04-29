<?php

namespace e7o\Moments\Request\Routers;

use \e7o\Moments\Moment;
use \e7o\Moments\Request\Request;
use \e7o\Moments\Response\Response;

interface Router
{
	public function callController(Moment $moment, Request $request): Response;
	public function callRoute(Moment $moment, Request $request, $route): Response;
	public function getRoute($routeId): ?array;
	public function buildUrl(Request $request, string $route, array $params = [], bool $absolute = false): string;
}
