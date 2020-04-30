<?php

namespace e7o\Moments\Request\Authentication;

use \e7o\Moments\Moment;
use \e7o\Moments\Request\Request;

class Authenticator
{
	protected $moment;
	
	public function __construct(Moment $moment)
	{
		$this->moment = $moment;
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
