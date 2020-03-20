<?php

namespace e7o\Moments\Database;

/**
* Opens a database connection, where $config contains an array containing some
* of the following options as usual:
* 
* - dsn -- just the plain DSN string
* - type -- like "sqlite" or "mysql"
* - host, database, charset # for e.g. mysql
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
					. ';host=' . $config['host'] . ';charset=' . ($config['charset'] ?? 'utf8')
				;
				$usr = $config['user'];
				$pw = $config['password'];
		}
		
		parent::__construct(
			$dsn,
			$usr,
			$pw,
			[
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			]
		);
	}
	
	/**
	* Simple helper to read a small resultset by query into an associative array.
	* All the data goes into an array, so keep track of your result size!
	*/
	public function get($query, $params)
	{
		$data = [];
		$q = $this->prepare($query);
		$r = $q->execute($params);
		if ($r == null) {
			return null;
		}
		while ($row = $q->fetch()) {
			$data[] = $row;
		}
		return $data;
	}
	
	/**
	* Simple helper for inserting a single dataset into a table. No special cases
	* like functions etc. allowed. Don't use this helper when you're inserting more
	* than one or two rows as this is the slowest possible way!
	*/
	public function insert($table, $data)
	{
		var_dump(array_keys($data), $data);
		$query = 'INSERT INTO ' . $table
			. '(' . implode(', ', array_keys($data))
			. ') VALUES (:' . implode(', :', array_keys($data)) . ')'
		;
		$q = $this->prepare($query);
		$r = $q->execute($data);
		if ($r) {
			return $this->lastInsertId();
		} else {
			return null;
		}
	}
}
