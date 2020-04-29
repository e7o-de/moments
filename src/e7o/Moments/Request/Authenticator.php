<?php

namespace e7o\Moments\Request;

use \e7o\Moments\Database\Connection as DatabaseConnection;
use \e7o\Moments\Request\Request;

class Authenticator
{
	protected $database;
	
	public function __construct(DatabaseConnection $database)
	{
		$this->database = $database;
	}
	
	/**
	* For use in controllers.
	*/
	public function getCurrentUser()
	{
		return null;
	}
	
	/**
	* This will be added to the template, so you can display the correct
	* username in the top (or whatever you like). The array (or object)
	* will be available as `{{ $.user }}`.
	*/
	public function getUserForTemplate()
	{
		return [];
	}
	
	/**
	* Just returns the info if the request is allowed or not.
	*/
	public function isAllowed(Request $request, array $route): bool
	{
		return true;
	}
	
	public function getAuthenticationRoute(): string
	{
		return 'error-403';
	}
}
