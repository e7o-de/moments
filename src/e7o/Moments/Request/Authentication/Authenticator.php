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
		$this->init();
	}
	
	/**
	* For the lazy ones (saves a copy-and-paste of the constructor.)
	*/
	protected function init()
	{
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
	
	/**
	* Returns true if there was a failed login try (means: show error message to user)
	*/
	public function wasUnsuccessfulLogin(): bool
	{
		return false;
	}
	
	/**
	* This is a method you should actually overwrite (nobody is forcing you
	* if you're relying on session timeouts or so).
	*/
	public function logout()
	{
		// Nothing to do here.
	}
	
	public function getAuthenticationRoute(): string
	{
		return 'error-403';
	}
}
