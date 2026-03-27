<?php

require_once __DIR__ . '/vendor/autoload.php';

use Rocket\Connection\Connection;
use Rocket\Migration\Rocket;
use Rocket\Migration\Migrator;

echo "=== Rocket Migration Test ===\n\n";

// Initialize database connection
try {
  Connection::initialize([
    'dsn' => 'mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=rocket',
    'user' => 'jhay',
    'password' => 'darkplace'
  ]);
  echo "✅ Database connection initialized\n";
} catch (Exception $e) {
  echo "❌ Database connection failed: " . $e->getMessage() . "\n";
  exit(1);
}

// Set connection for Rocket
Rocket::setConnection(Connection::getInstance());

echo "\n--- Running Migrations ---\n";
$migrator = new Migrator(Connection::getInstance(), __DIR__ . '/migrations');
$migrator->run();

echo "\n--- Verifying Tables ---\n";
$pdo = Connection::getInstance()->getPdo();
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables in database: " . implode(', ', $tables) . "\n";

echo "\n--- Verifying Foreign Key ---\n";
$result = $pdo->query("
    SELECT 
        TABLE_NAME, 
        CONSTRAINT_NAME, 
        REFERENCED_TABLE_NAME 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE REFERENCED_TABLE_NAME IS NOT NULL 
    AND TABLE_SCHEMA = 'rocket'
")->fetchAll(PDO::FETCH_ASSOC);

if (!empty($result)) {
  echo "Foreign keys found:\n";
  foreach ($result as $fk) {
    echo "  - {$fk['TABLE_NAME']}.{$fk['CONSTRAINT_NAME']} references {$fk['REFERENCED_TABLE_NAME']}\n";
  }
} else {
  echo "No foreign keys found\n";
}

echo "\n--- Testing Rollback ---\n";
$migrator->rollback(1);

echo "\n--- Testing Fresh ---\n";
$migrator->fresh();

echo "\n✅ Migration test completed!\n";
