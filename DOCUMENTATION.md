# Rocket ORM Documentation

## Overview

Rocket is a modern ORM (Object-Relational Mapping) for the Luxid Framework. It uses PHP 8 attributes for configuration, providing a clean, intuitive, and type-safe way to work with your database.

## Installation

Rocket is included by default in Luxid Framework projects. When you create a new Luxid project, Rocket is automatically installed and configured.

```bash
composer create-project luxid/framework my-app
```

## Configuration

Rocket uses your existing Luxid database configuration. The database connection is automatically configured from your `.env` file:

```env
DB_DSN=mysql:host=127.0.0.1;dbname=myapp
DB_USER=root
DB_PASSWORD=secret
```

## Defining Entities

Entities are PHP classes that represent database tables. Use attributes to define the table structure.

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

// Find all
$users = User::findAll(['is_active' => true], ['created_at' => 'DESC'], 10);

// Using Query Builder
$users = User::query()
    ->where('email', 'LIKE', '%@gmail.com')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->all();

// Count records
$count = User::query()->where('is_active', '=', true)->count();
```

### Updating Records

```php
$user = User::find(1);
$user->firstname = 'Jonathan';
$user->save(); // Automatically updates updated_at timestamp
```

### Deleting Records

```php
$user = User::find(1);
$user->delete();
```

## Query Builder

The query builder provides a fluent interface for building complex queries.

```php
use App\Entities\User;

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
}

// Usage
$user = User::find(1);
echo $user->fullName; // "John Doe"
echo $user->displayName; // "John Doe"
```

## Lifecycle Hooks

Override these methods to add custom logic at specific points:

```php
class User extends Entity
{
    protected function beforeSave(): void
    {
        if (!empty($this->password) && !$this->isPasswordHashed()) {
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        }
    }
    
    protected function afterSave(): void
    {
        // Clear cache, send welcome email, etc.
    }
    
    protected function beforeDelete(): void
    {
        // Clean up related data
    }
    
    protected function afterDelete(): void
    {
        // Log deletion
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

## Migrations

### Creating a Migration

```bash
php juice make:migration create_users_table
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
| `float()` | FLOAT column | `$column->float('price')` |
| `boolean()` | BOOLEAN column | `$column->boolean('is_active')` |
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

# Reset and re-run all migrations
php juice migrate:fresh
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
}
```

### Creating a Seeder

```bash
php juice make:seeder UserSeeder
```

### Seeder Structure

```php
<?php
namespace Seeds;

use Rocket\Seed\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create 10 users
        UserFactory::new()->count(10)->create();
        
        // Create an admin user
        UserFactory::new()->admin()->create();
    }
}
```

### Database Seeder

The `DatabaseSeeder` runs all seeders:

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

### Custom Validation

Add custom validation by overriding the `validate()` method:

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
}

$user = User::find(1);
return Response::json($user->toArray());
// Output: {'id': 1, 'email': 'john@example.com', 'name': 'John Doe'}
// Password is excluded
```

## Advanced Examples

### Complete User Entity

```php
<?php
namespace App\Entities;

use Rocket\ORM\Entity;
use Rocket\Attributes\Entity as EntityAttr;
use Rocket\Attributes\Column;
use Rocket\Attributes\Relations\HasMany;
use Rocket\Attributes\Relations\HasOne;
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
    
    #[Column]
    public bool $is_active = true;
    
    #[Column(autoCreate: true)]
    public string $created_at = '';
    
    #[Column(autoCreate: true, autoUpdate: true)]
    public string $updated_at = '';
    
    #[HasOne(Profile::class, 'user_id', 'id')]
    protected $profile;
    
    #[HasMany(Post::class, 'user_id', 'id')]
    protected $posts;
    
    public function getFullName(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }
    
    public function getDisplayName(): string
    {
        return $this->fullName ?: $this->email;
    }
    
    public function isPasswordHashed(): bool
    {
        return password_get_info($this->password)['algo'] !== 0;
    }
    
    protected function beforeSave(): void
    {
        if (!empty($this->password) && !$this->isPasswordHashed()) {
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        }
    }
    
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }
}
```

## Best Practices

1. **Always set default values** for typed properties to avoid uninitialized errors
2. **Use hidden columns** for sensitive data like passwords
3. **Add validation rules** to ensure data integrity
4. **Use lifecycle hooks** for password hashing and other side effects
5. **Leverage relationships** to keep your code clean and expressive
6. **Use factories and seeders** for consistent test data
7. **Keep migrations version-controlled** for team collaboration
8. **Use query builder** for complex queries instead of raw SQL

## Troubleshooting

### Common Issues

**"Typed property must not be accessed before initialization"**
- Add default values to all typed properties: `public int $id = 0;`

**"Class not found"**
- Run `composer dump-autoload` to regenerate the autoloader

**"Migration not found"**
- Ensure migration files are in the `migrations/` directory with correct naming

**"Validation failed"**
- Check `$entity->getErrors()` for detailed error messages

## Resources

- [Luxid Framework Documentation](https://luxid.dev/docs)