<?php

namespace e7o\Moments\Database;

class Connection extends \PDO
{
	protected $config;
	
	public function __construct($config)
	{
		$dsn = 'mysql:dbname=' . $config['database'] . ';host=' . $config['host'] . ';charset=' . $config['charset'];
		parent::__construct($dsn, $config['user'], $config['password']);
	}
}

