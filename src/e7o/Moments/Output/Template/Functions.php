<?php

namespace e7o\Moments\Output\Template;

class Functions
{
	public static function add(
		\e7o\Morosity\Morosity $template,
		\e7o\Moments\Request\Controllers\MomentsController $controller
	) {
		$template->addFunction(
			'route',
			function ($routeId, ...$params) use ($controller) {
				return $controller->buildRoute($routeId, $params);
			}
		);
		$template->addFunction(
			'request',
			function ($var) use ($controller) {
				return $controller->getRequest()->getParameter($var);
			}
		);
	}
}
