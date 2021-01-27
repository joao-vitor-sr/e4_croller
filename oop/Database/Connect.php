<?php

namespace Files\Database;

abstract class Connect 
{
	public function __construct()
	{
		$this->setPdo(new \PDO("mysql:host={$this->host};dbname={$this->dbName}", $this->user, $this->password));
	}

	protected $pdo;
	private $host = 'localhost';
	private $dbName = 'gestao';
	private $user = 'root';
	private $password = 'e4J53787:';

	// Getters and setters
	public function setPdo($pdo)
	{
		$this->pdo = $pdo;
	}

	public function getPdo()
	{
		return $this->pdo;
	}
}

