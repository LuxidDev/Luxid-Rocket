<?php

require_once __DIR__ . '/vendor/autoload.php';

use Rocket\Connection\Connection;
use Rocket\Attributes\Entity as EntityAttr;
use Rocket\Attributes\Column;
use Rocket\Attributes\Rules\Required;
use Rocket\Attributes\Rules\Email;
use Rocket\Attributes\Rules\Min;

// Define a test entity
#[EntityAttr(table: 'test_users')]
class TestUser extends Rocket\ORM\Entity
{
  #[Column(primary: true, autoIncrement: true)]
  public int $id = 0;

  #[Column]
  #[Required]
  #[Email]
  public string $email = '';

  #[Column]
  #[Required]
  #[Min(8)]
  public string $password = '';

  #[Column]
  #[Required]
  public string $name = '';

  #[Column(autoCreate: true)]
  public string $created_at = '';

  public function getDisplayName(): string
  {
    return $this->name;
  }
}

echo "=== Rocket ORM Entity Test ===\n\n";

// Initialize database connection (adjust for your environment)
try {
  Connection::initialize([
    'dsn' => 'mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=rocket',
    'user' => 'jhay',
    'password' => 'darkplace'
  ]);
  echo "✅ Database connection initialized\n";
} catch (Exception $e) {
  echo "❌ Database connection failed: " . $e->getMessage() . "\n";
  echo "   Make sure MySQL is running and database 'test' exists\n";
  exit(1);
}

// Test entity metadata
echo "\n--- Testing Entity Metadata ---\n";
$metadata = TestUser::getMetadata();
echo "Table name: " . $metadata->getTableName() . "\n";
echo "Primary key: " . $metadata->getPrimaryKey() . "\n";
echo "Columns: " . count($metadata->getColumns()) . " columns\n";

foreach ($metadata->getColumns() as $column) {
  echo "  - {$column->getProperty()} ({$column->getName()})";
  if ($column->isPrimary()) echo " [PRIMARY]";
  if ($column->isAutoIncrement()) echo " [AUTO_INCREMENT]";
  if ($column->isAutoCreate()) echo " [AUTO_CREATE]";
  echo "\n";
}

// Test creating a table (migration would handle this, but let's create manually for test)
echo "\n--- Creating test table ---\n";
$pdo = Connection::getInstance()->getPdo();

$sql = "CREATE TABLE IF NOT EXISTS test_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

try {
  $pdo->exec($sql);
  echo "✅ Table 'test_users' created\n";
} catch (Exception $e) {
  echo "❌ Failed to create table: " . $e->getMessage() . "\n";
}

// Test creating a user
echo "\n--- Testing Entity Save ---\n";

$user = new TestUser();
$user->email = 'test@example.com';
$user->password = 'password123';
$user->name = 'Test User';

echo "Before save - isNew: " . ($user->isNew ? 'true' : 'false') . "\n";

if ($user->save()) {
  echo "✅ User saved successfully!\n";
  echo "   ID: " . $user->id . "\n";
  echo "   Created at: " . $user->created_at . "\n";
  echo "   After save - isNew: " . ($user->isNew ? 'true' : 'false') . "\n";
} else {
  echo "❌ Failed to save user\n";
  print_r($user->getErrors());
}

// Test finding the user
echo "\n--- Testing Find ---\n";

$found = TestUser::find(1);
if ($found) {
  echo "✅ User found: {$found->name} ({$found->email})\n";
  echo "   Display name: " . $found->getDisplayName() . "\n";
} else {
  echo "❌ User not found\n";
}

// Test updating the user
echo "\n--- Testing Update ---\n";

$found->name = 'Updated Name';
if ($found->save()) {
  echo "✅ User updated successfully!\n";
  echo "   New name: " . $found->name . "\n";
} else {
  echo "❌ Failed to update user\n";
  print_r($found->getErrors());
}

// Test validation
echo "\n--- Testing Validation ---\n";

$invalidUser = new TestUser();
$invalidUser->email = 'invalid-email';
$invalidUser->password = 'short';
$invalidUser->name = '';

if ($invalidUser->save()) {
  echo "❌ Invalid user saved unexpectedly!\n";
} else {
  echo "✅ Validation failed as expected\n";
  echo "Errors:\n";
  foreach ($invalidUser->getErrors() as $field => $errors) {
    echo "  - $field: " . implode(', ', $errors) . "\n";
  }
}

// Clean up
echo "\n--- Cleaning Up ---\n";
$pdo->exec("DROP TABLE IF EXISTS test_users");
echo "✅ Table dropped\n";

echo "\n=== All tests completed ===\n";
