<?php

namespace e7o\Moments\Request\Authentication;

use \e7o\Moments\Database\Connection as DatabaseConnection;
use \e7o\Moments\Request\Request;

/**
* A database authenticator. Extend and overwrite `$dbTable` to specify the database
* table you're using; if you have e.g. a deleted or blocked flag, add this in
* `$dbAdditionalWhere.
* 
* Minimum required table (of course you should add a UNIQUE constraint, a primary key,
* an email address etc.):
* 
* ```sql
* CREATE TABLE `users`(
* 	`name` TINYTEXT NOT NULL,
* 	`password` TINYTEXT NOT NULL
* );
* ```
* 
* This code is far from being completed, it's still missing basic features like
* a failed login counter to prevent brute force attacks etc. Overwrite `failedLogin($user)`
* and increase a counter, set the last try time or similar.
*/
class SimpleDatabaseAuthenticator extends SimpleConfigAuthenticator
{
	protected $dbTable = 'users';
	protected $dbAdditionalWhere = 'AND 1'; // Could be sth like "AND deleted IS NULL"
	
	public function getCurrentUser()
	{
		if (!empty($this->current)) {
			if (empty($this->userCache[$this->current])) {
				$this->getUserPass($this->current);
			}
			return $this->userCache[$this->current];
		}
	}
	
	public function getUserForTemplate()
	{
		return $this->getCurrentUser();
	}
	
	public function changePassword($user, $password)
	{
		$hash = $this->hashPassword($user, $password);
		$this->moment->getService('database')->update(
			$this->dbTable,
			['password' => $hash],
			'name = :username',
			['username' => $user]
		);
		$this->userCache[$user]['password'] = $hash;
		if ($user === $this->current) {
			$this->setAuthCookie();
		}
		return true;
	}
	
	protected function getUserPass($user)
	{
		if (isset($this->userCache[$user])) {
			return $this->userCache[$user]['password'];
		}
		
		$pw = $this->moment->getService('database')->get(
			'SELECT id, name, password FROM '
				. $this->dbTable
				. ' WHERE name = :user ' . $this->dbAdditionalWhere,
			[
				'user' => $user,
			]
		);
		
		if (count($pw) == 0) {
			$this->userCache[$user] = null;
		} else {
			$this->userCache[$user] = $pw[0];
		}
		
		return $this->userCache[$user]['password'];
	}
	
	protected function checkPassword($user, $given, $expected)
	{
		return $this->hashPassword($user, $given) === $expected;
	}
	
	public function hashPassword($user, $pass)
	{
		return hash('whirlpool', $pass);
	}
}
