<?php

namespace e7o\Moments\Request\Authentication;

use \e7o\Moments\Moment;
use \e7o\Moments\Request\Request;

/**
* Simple low-security (only legit if anyways most people know the password, like
* on an internal tool) implementation of a password check. Needs a few things from
* end user side.
* 
* First: The user credentials table in your `config.json`:
* 
* ```javascript
* "users": {
* 	"admin": "topsecret"
* },
* ```
* 
* Second: A login form on `error-403` route (`error_403.htm` by default):
* 
* ```html
* <form method="post" action="{{ $.route.requesturi }}">
* 	<input type="text" name="user" required="required" /><br/>
* 	<input type="password" name="password" required="required" /><br/>
* 	<button type="submit">Login</button>
* </form>
* ```
*/
class SimpleConfigAuthenticator extends Authenticator
{
	protected $users;
	protected $current = null;
	protected $secret;
	protected $config;
	protected $userCache = [];
	
	/**
	* Some options to overwrite HTML field names, if you really need to.
	*/
	protected $formFieldUser = 'user';
	protected $formFieldPassword = 'password';
	protected $cookieName = 'moments-auth';
	
	protected function init()
	{
		$this->config = $this->moment->getService('config');
		$this->secret = $this->config->get('secret', 'emergency-not-so-secret');
		$this->userCache = $this->config->get('users', []);
	}
	
	/**
	* This could be replaced by e.g. a database request getting the user
	* password from a table. It's recommended to cache the value, as this
	* method might get called twice (not an issue for a stupid array).
	*/
	protected function getUserPass($user)
	{
		return $this->userCache[$user] ?? null;
	}
	
	public function getCurrentUser()
	{
		return $this->current;
	}
	
	public function getUserForTemplate()
	{
		return $this->current;
	}
	
	public function isAllowed(Request $request, array $route): bool
	{
		$user = $request[$this->formFieldUser];
		$password = $request[$this->formFieldPassword];
		$auth = $request[$this->cookieName];
		
var_dump($auth);die;
		// Check existing authentication
		if (!empty($auth)) {
			$user = $this->checkAuthCookieString($auth);
			if (!empty($user)) {
				// Identified based on existing cookie
				$this->current = $user;
				return true;
			} else {
				// Remove invalid cookie
				$this->logout();
			}
		}
		
		// Check login
		if (!empty($user) && !empty($password) && $userpass = $this->getUserPass($user)) {
			if ($this->checkPassword($user, $password, $userpass)) {
				$this->current = $user;
				setcookie($this->cookieName, $this->getAuthCookieString($user, $password), time() + 86400);
				return true;
			}
		}
		
		return false;
	}
	
	public function logout()
	{
		setcookie($this->cookieName, '', time() - 10);
	}
	
	/**
	* Overwrite this to check e.g. the sha1 hash or so.
	*/
	protected function checkPassword($user, $given, $expected)
	{
		return $given === $expected;
	}
	
	protected function getAuthCookieString($user)
	{
		$salt = base_convert(bin2hex(random_bytes(8)), 16, 36);
		return $salt . ':' . $user . ':' . $this->hash($salt, $user);
	}
	
	protected function checkAuthCookieString($string)
	{
		list($salt, $user, $hash) = explode(':', $string);
		$expected = $this->hash($salt, $user);
		if ($expected == $hash) {
			return $user;
		} else {
			return null;
		}
	}
	
	protected function hash($salt, $user)
	{
		return hash('whirlpool', $this->secret . $salt . $user . $this->getUserPass($user));
	}
}
