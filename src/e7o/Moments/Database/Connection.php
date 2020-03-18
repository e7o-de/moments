<?php

namespace e7o\Moments\Database;

/**
* Opens a database connection, where $config contains an array containing some
* of the following options as usual:
* 
* - dsn -- just the plain DSN string
* - type -- like "sqlite" or "mysql"
* - host, dbname, charset # for e.g. mysql
* - file # for sqlite; should be something like "${root}/data/storage.sqlite"
*   (absolute path required by sqlite connector)
*/
class Connection extends \PDO
{
	protected $config;
	
	public function __construct($config)
	{
		switch ($config['type']) {
			case 'sqlite':
				$dsn = 'sqlite:' . $config['file'];
				$usr = null;
				$pw = null;
				break;
			default:
				$dsn = ($config['type'] ?? 'mysql') . ':dbname=' . $config['database']
					. ';host=' . $config['host'] . ';charset=' . $config['charset']
				;
				$usr = $config['user'];
				$pw = $config['password'];
		}
		
		parent::__construct($dsn, $usr, $pw);
	}
}

