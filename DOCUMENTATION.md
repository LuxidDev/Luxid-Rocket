# Rocket ORM Documentation

## Overview

Rocket is a modern ORM (Object-Relational Mapping) for the Luxid Framework. It uses PHP 8 attributes for configuration, providing a clean, intuitive, and type-safe way to work with your database.

## Installation

Rocket is included by default in Luxid Framework projects. When you create a new Luxid project, Rocket is automatically installed and configured.

```bash
composer create-project luxid/framework my-app
cd my-app
php juice db:migrate
php juice seed
```

## Configuration

Rocket uses your existing Luxid database configuration. The database connection is automatically configured from your `.env` file:

```env
# Database Configuration
DB_DSN=mysql:host=127.0.0.1;dbname=myapp
DB_USER=root
DB_PASSWORD=secret

# Platform-specific (Linux/Arch with MariaDB)
# DB_DSN=mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=myapp

# macOS with MAMP
# DB_DSN=mysql:unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock;dbname=myapp
```

## Defining Entities

Entities are PHP classes that represent database tables. Use PHP 8 attributes to define the table structure, columns, and validation rules.

### Basic Entity

```php
<?php
namespace App\Entities;

use Rocket\ORM\Entity;
use Rocket\Attributes\Entity as EntityAttr;
use Rocket\Attributes\Column;
use Rocket\Attributes\Rules\Required;
use Rocket\Attributes\Rules\Email;
use Rocket\Attributes\Rules\Min;
use Rocket\Attributes\Rules\Unique;

#[EntityAttr(table: 'users')]
class User extends Entity
{
    #[Column(primary: true, autoIncrement: true)]
    public int $id = 0;
    
    #[Column]
    #[Required]
    #[Email]
    #[Unique]
    public string $email = '';
    
    #[Column(hidden: true)]
    #[Required]
    #[Min(8)]
    public string $password = '';
    
    #[Column]
    #[Required]
    public string $firstname = '';
    
    #[Column]
    #[Required]
    public string $lastname = '';
    
    #[Column(autoCreate: true)]
    public string $created_at = '';
    
    #[Column(autoCreate: true, autoUpdate: true)]
    public string $updated_at = '';
}
```

### Column Attributes

| Attribute | Description |
|-----------|-------------|
| `#[Column(primary: true)]` | Sets column as primary key |
| `#[Column(autoIncrement: true)]` | Auto-incrementing integer |
| `#[Column(hidden: true)]` | Excludes from JSON serialization |
| `#[Column(autoCreate: true)]` | Automatically sets on create (like timestamps) |
| `#[Column(autoUpdate: true)]` | Automatically updates on every save |
| `#[Column(nullable: true)]` | Allows NULL values |
| `#[Column(default: value)]` | Sets default value |

### Validation Rules

| Rule | Description | Example |
|------|-------------|---------|
| `#[Required]` | Field cannot be empty | `#[Required]` |
| `#[Email]` | Must be valid email | `#[Email]` |
| `#[Min(8)]` | Minimum length/value | `#[Min(8)]` |
| `#[Max(100)]` | Maximum length/value | `#[Max(100)]` |
| `#[Unique]` | Must be unique in table | `#[Unique]` |
| `#[In(['a', 'b'])]` | Must be in allowed values | `#[In(['pending', 'completed'])]` |

## Basic CRUD Operations

### Creating Records

```php
$user = new User();
$user->email = 'john@example.com';
$user->password = 'password123';
$user->firstname = 'John';
$user->lastname = 'Doe';

if ($user->save()) {
    echo "User created! ID: {$user->id}";
} else {
    print_r($user->getErrors()); // Show validation errors
}
```

### Finding Records

```php
// Find by ID
$user = User::find(1);

// Find one by conditions
$user = User::findOne(['email' => 'john@example.com']);

// Find all with conditions
$users = User::findAll(['is_active' => true], ['created_at' => 'DESC'], 10);

// Using Query Builder
$users = User::query()
    ->where('email', 'LIKE', '%@gmail.com')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->all();

// Count records
$count = User::query()->where('is_active', '=', true)->count();

// Get total count
$total = User::count();

// Check if any records exist
if (User::exists()) {
    echo "Users found!";
}

// Get first record
$firstUser = User::first();

// Get last record
$lastUser = User::last();

// Get random records
$randomUsers = User::random(3);
```

### Updating Records

```php
$user = User::find(1);
$user->firstname = 'Jonathan';
$user->save(); // Automatically updates updated_at timestamp
```

### Deleting Records

```php
// Delete a single record
$user = User::find(1);
$user->delete();

// Delete all records
User::deleteAll();

// Truncate table (delete all and reset auto-increment)
User::truncate();
```

## Query Builder

The query builder provides a fluent interface for building complex queries.

```php
use App\Entities\User;

// Basic queries
$users = User::query()
    ->select(['id', 'name', 'email'])
    ->where('is_active', '=', true)
    ->where('age', '>=', 18)
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->offset(20)
    ->all();

// Where IN
$users = User::query()
    ->whereIn('id', [1, 2, 3])
    ->all();

// Where NULL
$users = User::query()
    ->whereNull('deleted_at')
    ->all();

// Where NOT NULL
$users = User::query()
    ->whereNotNull('email_verified_at')
    ->all();
```

## Computed Properties

Use the `get` prefix to create computed properties that aren't stored in the database:

```php
class User extends Entity
{
    // ... columns
    
    public function getFullName(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }
    
    public function getDisplayName(): string
    {
        return $this->fullName ?? $this->email;
    }
    
    public function getInitials(): string
    {
        return strtoupper(substr($this->firstname, 0, 1) . substr($this->lastname, 0, 1));
    }
}

// Usage
$user = User::find(1);
echo $user->fullName;    // "John Doe"
echo $user->displayName; // "John Doe"
echo $user->initials;    // "JD"
```

## Lifecycle Hooks

Override these methods to add custom logic at specific points:

```php
class User extends Entity
{
    protected function beforeSave(): void
    {
        // Hash password before saving
        if (!empty($this->password) && !$this->isPasswordHashed()) {
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        }
        
        // Auto-set timestamps
        if ($this->isNew && empty($this->created_at)) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        $this->updated_at = date('Y-m-d H:i:s');
    }
    
    protected function afterSave(): void
    {
        // Clear cache, send welcome email, etc.
        Cache::forget('user_' . $this->id);
    }
    
    protected function beforeDelete(): void
    {
        // Soft delete related records
        $this->posts()->update(['deleted_at' => now()]);
    }
    
    protected function afterDelete(): void
    {
        // Log deletion
        Log::info("User {$this->id} was deleted");
    }
    
    private function isPasswordHashed(): bool
    {
        return password_get_info($this->password)['algo'] !== 0;
    }
}
```

## Relationships

### HasOne Relationship

One-to-one relationship where the current entity has one related entity.

```php
use Rocket\Attributes\Relations\HasOne;

class User extends Entity
{
    #[HasOne(Profile::class, 'user_id', 'id')]
    protected $profile;
}

class Profile extends Entity
{
    #[Column]
    public string $bio = '';
    
    #[Column]
    public int $user_id = 0;
}

// Usage
$user = User::find(1);
echo $user->profile->bio; // Loads profile automatically
```

### HasMany Relationship

One-to-many relationship where the current entity has many related entities.

```php
use Rocket\Attributes\Relations\HasMany;

class User extends Entity
{
    #[HasMany(Post::class, 'user_id', 'id')]
    protected $posts;
}

class Post extends Entity
{
    #[Column]
    public string $title = '';
    
    #[Column]
    public string $content = '';
    
    #[Column]
    public int $user_id = 0;
}

// Usage
$user = User::find(1);
foreach ($user->posts as $post) {
    echo $post->title;
}

// Create related record
$user->posts()->create(['title' => 'New Post', 'content' => '...']);
```

### BelongsTo Relationship

Inverse of HasOne/HasMany. The related entity belongs to the current entity.

```php
use Rocket\Attributes\Relations\BelongsTo;

class Post extends Entity
{
    #[BelongsTo(User::class, 'user_id', 'id')]
    protected $author;
}

// Usage
$post = Post::find(1);
echo $post->author->name; // Loads the author automatically
```

## Entity Helper Methods

Rocket provides several convenient helper methods on all entities:

| Method | Description | Example |
|--------|-------------|---------|
| `count()` | Get total record count | `User::count()` |
| `exists()` | Check if any records exist | `User::exists()` |
| `first()` | Get the first record | `User::first()` |
| `last()` | Get the last record | `User::last()` |
| `random($limit)` | Get random records | `User::random(5)` |
| `deleteAll()` | Delete all records | `User::deleteAll()` |
| `truncate()` | Delete all records and reset auto-increment | `User::truncate()` |

### Example Usage

```php
// Count users
$totalUsers = User::count();

// Check if there are any active users
if (User::exists()) {
    echo "There are users!";
}

// Get the newest user
$newestUser = User::last();

// Get 3 random products for a "You might also like" section
$randomProducts = Product::random(3);

// Reset the users table for testing
User::truncate();

// Clean up old records
User::where('last_login', '<', date('Y-m-d', strtotime('-1 year')))->deleteAll();
```

## Migrations

### Creating a Migration

```bash
php juice make:migration create_users_table
php juice make:migration add_email_to_users
php juice make:migration create_products_table
```

### Migration Structure

```php
<?php
use Rocket\Migration\Migration;
use Rocket\Migration\Rocket;

class m00001_create_users_table extends Migration
{
    public function up(): void
    {
        Rocket::table('users', function($column) {
            $column->id('id');
            $column->string('email')->unique();
            $column->string('password')->hidden();
            $column->string('firstname');
            $column->string('lastname');
            $column->timestamps();
        });
    }
    
    public function down(): void
    {
        Rocket::drop('users');
    }
}
```

### Column Types

| Method | Description | Example |
|--------|-------------|---------|
| `id()` | Auto-incrementing primary key | `$column->id('id')` |
| `string()` | VARCHAR column | `$column->string('email')` |
| `text()` | TEXT column | `$column->text('content')` |
| `integer()` | INT column | `$column->integer('age')` |
| `float()` | FLOAT column | `$column->float('rating')` |
| `decimal()` | DECIMAL column | `$column->decimal('price', 10, 2)` |
| `boolean()` | BOOLEAN column | `$column->boolean('is_active')` |
| `datetime()` | DATETIME column | `$column->datetime('published_at')` |
| `timestamps()` | created_at + updated_at | `$column->timestamps()` |
| `softDeletes()` | deleted_at column | `$column->softDeletes()` |

### Column Modifiers

| Modifier | Description | Example |
|----------|-------------|---------|
| `->unique()` | Add unique constraint | `$column->string('email')->unique()` |
| `->nullable()` | Allow NULL values | `$column->string('bio')->nullable()` |
| `->default(value)` | Set default value | `$column->boolean('active')->default(true)` |
| `->index()` | Add index | `$column->integer('status')->index()` |
| `->hidden()` | Hide from serialization | `$column->string('password')->hidden()` |

### Foreign Keys

```php
$column->foreign('user_id')
    ->references('id')
    ->on('users')
    ->cascadeOnDelete();
```

Actions:
- `cascadeOnDelete()` - Delete child records when parent is deleted
- `setNullOnDelete()` - Set foreign key to NULL when parent is deleted
- `restrictOnDelete()` - Prevent deletion if child records exist
- `cascadeOnUpdate()` - Update foreign key when parent is updated

### Running Migrations

```bash
# Run pending migrations
php juice migrate

# Rollback last batch
php juice migrate:rollback

# Rollback all migrations
php juice migrate:reset

# Reset and re-run all migrations (with optional seed)
php juice migrate:fresh
php juice migrate:fresh --seed
```

## Seeding

### Creating a Factory

```bash
php juice make:factory UserFactory
```

### Factory Structure

```php
<?php
namespace Seeds;

use Rocket\Seed\Factory;
use Rocket\Seed\Faker;
use App\Entities\User;

class UserFactory extends Factory
{
    protected static function getEntityClass(): string
    {
        return User::class;
    }
    
    protected function definition(): array
    {
        return [
            'name' => Faker::name(),
            'email' => Faker::unique()->email(),
            'password' => 'password123',
            'is_active' => true,
        ];
    }
    
    public function admin(): self
    {
        return $this->state([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);
    }
    
    public function inactive(): self
    {
        return $this->state([
            'is_active' => false,
        ]);
    }
}
```

### Creating a Seeder

```bash
php juice make:seeder UserSeeder
```

### Seeder Structure with Truncate

```php
<?php
namespace Seeds;

use Rocket\Seed\Seeder;
use App\Entities\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        echo "🌱 Seeding users...\n";
        
        // Clean the table before seeding (no raw SQL!)
        User::truncate();
        
        // Create admin user
        $admin = new User();
        $admin->email = 'admin@example.com';
        $admin->password = 'admin123';
        $admin->firstname = 'Admin';
        $admin->lastname = 'User';
        $admin->save();
        echo "  ✓ Created admin user\n";
        
        // Create 10 regular users
        for ($i = 1; $i <= 10; $i++) {
            $user = new User();
            $user->email = "user{$i}@example.com";
            $user->password = 'password123';
            $user->firstname = "User";
            $user->lastname = "{$i}";
            $user->save();
        }
        echo "  ✓ Created 10 regular users\n";
        
        echo "✅ User seeding completed!\n";
    }
}
```

### Database Seeder

The `DatabaseSeeder` runs all seeders in order:

```php
<?php
namespace Seeds;

use Rocket\Seed\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(PostSeeder::class);
        $this->call(CommentSeeder::class);
    }
}
```

### Running Seeders

```bash
# Run all seeders
php juice seed

# Run specific seeder
php juice seed UserSeeder

# Fresh migrate and seed
php juice db:fresh --seed
```

## Validation

Validation runs automatically when calling `save()`. You can also validate manually:

```php
$user = new User();
$user->email = 'invalid-email';

if (!$user->validate()) {
    foreach ($user->getErrors() as $field => $errors) {
        echo "{$field}: " . implode(', ', $errors);
    }
}
```

### Custom Validation Rules

Create custom validation by extending the base validation:

```php
class User extends Entity
{
    public function validate(): bool
    {
        // Run default validation first
        if (!parent::validate()) {
            return false;
        }
        
        // Add custom validation
        if (strpos($this->email, 'example.com') !== false) {
            $this->addError('email', 'Email cannot be from example.com');
            return false;
        }
        
        if (strlen($this->password) < 8) {
            $this->addError('password', 'Password must be at least 8 characters');
            return false;
        }
        
        return true;
    }
}
```

## Working with API Responses

The `toArray()` method automatically handles hidden columns:

```php
class User extends Entity
{
    #[Column(hidden: true)]
    public string $password = '';
    
    #[Column(hidden: true)]
    public string $remember_token = '';
}

$user = User::find(1);
return Response::json($user->toArray());
// Output: {'id': 1, 'email': 'john@example.com', 'name': 'John Doe', 'created_at': '...'}
// Password and remember_token are excluded
```

## CLI Commands Reference

```bash
# Database
php juice db:create                # Create database
php juice db:migrate               # Run migrations
php juice db:rollback              # Rollback last batch
php juice db:reset                 # Rollback all migrations
php juice db:fresh                 # Drop all tables and re-migrate
php juice db:fresh --seed          # Fresh migrate and seed
php juice seed                     # Run all seeders
php juice seed UserSeeder          # Run specific seeder

# Code Generation
php juice make:entity Product      # Create entity
php juice make:migration           # Create migration
php juice make:seeder              # Create seeder
php juice make:factory             # Create factory

# Info
php juice status                   # Check application status
php juice routes                   # List all routes
php juice version                  # Show version
```

## Best Practices

1. **Always set default values** for typed properties to avoid uninitialized errors
   ```php
   public int $id = 0;
   public string $name = '';
   ```

2. **Use hidden columns** for sensitive data like passwords and tokens
   ```php
   #[Column(hidden: true)]
   public string $password = '';
   ```

3. **Add validation rules** to ensure data integrity before saving
   ```php
   #[Required]
   #[Email]
   #[Unique]
   public string $email = '';
   ```

4. **Use lifecycle hooks** for side effects like password hashing and timestamps
   ```php
   protected function beforeSave(): void
   {
       if (!empty($this->password) && !$this->isPasswordHashed()) {
           $this->password = password_hash($this->password, PASSWORD_DEFAULT);
       }
       
       if ($this->isNew && empty($this->created_at)) {
           $this->created_at = date('Y-m-d H:i:s');
       }
       $this->updated_at = date('Y-m-d H:i:s');
   }
   ```

5. **Leverage relationships** to keep your code clean and expressive
   ```php
   $user = User::find(1);
   foreach ($user->posts as $post) {
       echo $post->title;
   }
   ```

6. **Use helper methods** instead of raw SQL
   ```php
   // Instead of: $this->db->execute("TRUNCATE TABLE users");
   User::truncate();
   
   // Instead of: $this->db->execute("DELETE FROM users");
   User::deleteAll();
   ```

7. **Use factories and seeders** for consistent test data
   ```bash
   php juice make:factory UserFactory
   php juice seed
   ```

8. **Keep migrations version-controlled** for team collaboration
   ```bash
   git add migrations/
   git commit -m "Add users table migration"
   ```

9. **Use query builder** for complex queries instead of raw SQL
   ```php
   $products = Product::query()
       ->where('price', '>', 100)
       ->where('stock', '>', 0)
       ->orderBy('name')
       ->limit(10)
       ->all();
   ```

10. **Use computed properties** for derived values
    ```php
    public function getTotalPrice(): float
    {
        return $this->quantity * $this->unit_price;
    }
    ```

## Troubleshooting

### Common Issues and Solutions

**"Typed property must not be accessed before initialization"**
- Add default values to all typed properties: `public int $id = 0;`

**"Class not found"**
- Run `composer dump-autoload` to regenerate the autoloader

**"Migration not found"**
- Ensure migration files are in the `migrations/` directory with correct naming (`m00001_*.php`)

**"Validation failed"**
- Check `$entity->getErrors()` for detailed error messages
- Example: `print_r($user->getErrors());`

**"Connection failed"**
- Verify your `.env` database configuration
- Check if MySQL/MariaDB is running: `systemctl status mariadb` (Linux) or `brew services list` (macOS)

**"Table already exists"**
- Run `php juice db:rollback` to revert, then fix migration and run `php juice db:migrate`

**"Foreign key constraint fails"**
- Ensure referenced table exists and has the referenced column
- Use `->nullable()` if the foreign key can be null

## Resources

- [Luxid Framework Documentation](https://luxid.dev/docs)
- [Rocket ORM GitHub Repository](https://github.com/LuxidDev/Luxid-Rocket)
- [Issue Tracker](https://github.com/LuxidDev/Luxid-Rocket/issues)
```
