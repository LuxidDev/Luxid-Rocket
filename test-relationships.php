<?php

require_once __DIR__ . '/vendor/autoload.php';

use Rocket\Connection\Connection;
use Rocket\Migration\Rocket;
use Rocket\Tests\User;
use Rocket\Tests\Post;
use Rocket\Tests\Profile;

echo "=== Rocket ORM Relationships Test ===\n\n";

// Initialize connection
Connection::initialize([
  'dsn' => 'mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=rocket',
  'user' => 'jhay',
  'password' => 'darkplace'
]);

// Set connection for Rocket
Rocket::setConnection(Connection::getInstance());

// Create tables
echo "Creating tables...\n";
$pdo = Connection::getInstance()->getPdo();

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

// Create a user
$user = new User();
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->save();

echo "Created user: ID {$user->id}, Name: {$user->name}\n";

// Create a profile
$profile = new Profile();
$profile->user_id = $user->id;
$profile->bio = 'Software Developer';
$profile->avatar = 'avatar.jpg';
$profile->save();

echo "Created profile for user\n";

// Create posts
$post1 = new Post();
$post1->user_id = $user->id;
$post1->title = 'First Post';
$post1->content = 'This is my first post';
$post1->save();

$post2 = new Post();
$post2->user_id = $user->id;
$post2->title = 'Second Post';
$post2->content = 'This is my second post';
$post2->save();

echo "Created 2 posts for user\n\n";

// Test relationships
echo "--- Testing Relationships ---\n";

// Test HasOne (User -> Profile)
$user = User::find(1);
echo "User: {$user->name}\n";
echo "Profile: {$user->profile->bio}\n\n";

// Test HasMany (User -> Posts)
echo "Posts by {$user->name}:\n";
foreach ($user->posts as $post) {
  echo "  - {$post->title}: {$post->content}\n";
}
echo "\n";

// Test BelongsTo (Post -> User)
$post = Post::find(1);
echo "Post '{$post->title}' by: {$post->author->name}\n\n";

// Test BelongsTo (Profile -> User)
$profile = Profile::find(1);
echo "Profile for: {$profile->user->name}\n\n";

echo "✅ Relationship tests completed!\n";
