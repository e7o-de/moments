<?php

namespace __NAMESPACE_REL__\Controllers;

use \e7o\Moments\Request\Controllers\MomentsController;

class Index extends MomentsController
{
	public function indexAction()
	{
		// $db = $this->get('database');
		return [
			'text' => 'Hello World!<br/>12345 67890',
		];
	}
	
	public function jsonAction()
	{
		return ['success' => true];
	}
}
