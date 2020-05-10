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
	* Initialises the current user information (like doing the login/logout process).
	* The route is available, but it could still change at this point due to redirects.
	* Take care, this method can get called twice in such cases. For "normal"
	* implementations this would be just a duplicate database request (if you don'T cache),
	* if you're relying on $route, you should be very careful and reset stuff first.
	*/
	public function checkLogin(Request $request, array $route)
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
	* Just returns the info if the request to the given route is allowed or not.
	* As you have the route, you can put `"requireGroup": "admin"` or so in your
	* config file and compare here.
	*/
	public function isAllowed(Request $request, array $route): bool
	{
		return true;
	}
	
	/**
	* Checks if the user has the `$right`, whatever this is.
	*/
	public function hasRights($right): bool
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
	* Is called in case of a failed login (wrong password). Your turn to take further
	* actions like banning the IP address or user account or so.
	*/
	protected function failedLogin($user)
	{
	}
	
	/**
	* Called on a successful login. Send a welcome email, log the access, reset
	* counters ...
	*/
	protected function succeededLogin($user)
	{
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
