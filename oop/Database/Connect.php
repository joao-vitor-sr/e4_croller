<?php

namespace Files\Database;

include_once __DIR__ . '/../../vendor/autoload.php';

// dotenv configuration
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

abstract class Connect
{
	public function __construct()
	{
		$this->host = $_ENV['HOST'];
		$this->port = $_ENV['PORT'];
		$this->userName = $_ENV['USER_NAME'];
		$this->password = $_ENV['PASSWORD'];
		$this->database = $_ENV['DATABASE'];
		
		$this->setPdo(new \PDO("mysql:host={$this->host};port={$this->port};dbname={$this->database}", $this->userName, $this->password));
	}

	private $host;
	private $port;
	private $userName;
	private $password;
	private $database;

	protected $pdo;

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
