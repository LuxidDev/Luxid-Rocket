<?php

require_once __DIR__ . '/vendor/autoload.php';

use Rocket\Connection\Connection;

// Initialize database connection (adjust to your environment)
Connection::initialize([
  'dsn' => 'mysql:host=127.0.0.1;dbname=test',
  'user' => 'root',
  'password' => ''
]);

echo "Rocket ORM initialized successfully!\n";
