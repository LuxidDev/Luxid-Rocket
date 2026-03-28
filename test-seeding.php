<?php

require_once __DIR__ . '/vendor/autoload.php';

use Rocket\Connection\Connection;
use Rocket\Migration\Rocket;
use Rocket\Seed\SeederRunner;

echo "=== Rocket ORM Seeding Test ===\n\n";

// Initialize connection
Connection::initialize([
  'dsn' => 'mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=rocket',
  'user' => 'jhay',
  'password' => 'darkplace'
]);

// Set connection for Rocket
Rocket::setConnection(Connection::getInstance());

// Create tables first (if not exists)
$pdo = Connection::getInstance()->getPdo();

echo "Creating tables...\n";
$pdo->exec("DROP TABLE IF EXISTS posts, profiles, users");

$pdo->exec("CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE
)");

$pdo->exec("CREATE TABLE profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bio TEXT,
    avatar VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$pdo->exec("CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

echo "✅ Tables created\n\n";

// Run seeders
echo "Running seeders...\n";
$runner = new SeederRunner(Connection::getInstance(), __DIR__ . '/seeds');
$runner->run();

echo "\n--- Verifying Seeded Data ---\n";

// Verify users
$users = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch();
echo "Total users: {$users['count']}\n";

// Verify profiles
$profiles = $pdo->query("SELECT COUNT(*) as count FROM profiles")->fetch();
echo "Total profiles: {$profiles['count']}\n";

// Verify posts
$posts = $pdo->query("SELECT COUNT(*) as count FROM posts")->fetch();
echo "Total posts: {$posts['count']}\n";

// Show sample data
echo "\n--- Sample Data ---\n";
$sampleUsers = $pdo->query("SELECT * FROM users LIMIT 3")->fetchAll();
foreach ($sampleUsers as $user) {
  echo "User: {$user['name']} ({$user['email']})\n";

  $postCount = $pdo->query("SELECT COUNT(*) as count FROM posts WHERE user_id = {$user['id']}")->fetch();
  echo "  Posts: {$postCount['count']}\n";
}

echo "\n✅ Seeding test completed!\n";
