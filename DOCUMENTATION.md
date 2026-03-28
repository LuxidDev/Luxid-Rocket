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

## Migrations

### Intelligent Migration Generation

Rocket's migration system is smart enough to understand what you're trying to do based on the migration name you provide. When you run `php juice make:migration`, the system analyzes the name and automatically generates the appropriate migration template.

#### How It Works

The migration generator uses **naming conventions** to determine the migration type:

| Migration Name Pattern | Generated Type | Description |
|------------------------|----------------|-------------|
| `create_*_table` | **Create Table** | Creates a new table using `Rocket::table()` |
| `add_*_to_*` | **Add Column** | Adds columns to an existing table using `Rocket::alter()` |
| `drop_*_from_*` | **Drop Column** | Removes columns from an existing table using `Rocket::alter()` |
| `alter_*` | **Alter Table** | General table alterations using `Rocket::alter()` |
| Anything else | **Generic** | Custom migration with placeholder code |

#### Examples of Smart Detection

```bash
# Create Table Migration
php juice make:migration create_users_table
# → Generates Rocket::table() template

php juice make:migration create_products_table  
# → Generates Rocket::table() template

# Add Column Migration
php juice make:migration add_email_to_users
# → Generates Rocket::alter() template for adding columns

php juice make:migration add_price_to_products
# → Generates Rocket::alter() template

# Drop Column Migration
php juice make:migration drop_old_column_from_users
# → Generates Rocket::alter() template for dropping columns

# Alter Table Migration
php juice make:migration alter_users_table
# → Generates general Rocket::alter() template

# Generic Migration (no specific pattern)
php juice make:migration update_user_data
# → Generates a generic template with comments
```

### Create Table Migration (CREATE)

When you create a table migration, the system generates a `Rocket::table()` block. This is for **creating new tables from scratch**.

```php
<?php
// migrations/m00002_create_products_table.php

use Rocket\Migration\Migration;
use Rocket\Migration\Rocket;
use Rocket\Migration\Column;

class m00002_create_products_table extends Migration
{
    public function up(): void
    {
        // Rocket::table() creates a new table
        Rocket::table('products', function($column) {
            $column->id('id');
            $column->string('name');
            $column->text('description')->nullable();
            $column->decimal('price', 10, 2);
            $column->integer('stock')->default(0);
            $column->timestamps();
        });
    }
    
    public function down(): void
    {
        // Rocket::drop() removes the entire table
        Rocket::drop('products');
    }
}
```

**Key Points:**
- `Rocket::table()` is used to **create a new table**
- All columns are defined at once
- The `down()` method drops the entire table
- This is the starting point for new tables

### Add Column Migration (ALTER)

When you add columns to an existing table, the system generates a `Rocket::alter()` block. This is for **modifying existing tables**.

```bash
php juice make:migration add_sku_to_products
```

```php
<?php
// migrations/m00003_add_sku_to_products.php

use Rocket\Migration\Migration;
use Rocket\Migration\Rocket;

class m00003_add_sku_to_products extends Migration
{
    public function up(): void
    {
        // Rocket::alter() modifies an existing table
        Rocket::alter('products', function($column) {
            $column->string('sku')->unique();
            $column->string('brand')->nullable();
        });
    }
    
    public function down(): void
    {
        // Rollback by dropping the added columns
        Rocket::alter('products', function($column) {
            $column->dropColumn('sku');
            $column->dropColumn('brand');
        });
    }
}
```

**Key Points:**
- `Rocket::alter()` is used to **modify existing tables**
- You add new columns, drop existing ones, or modify column definitions
- The `down()` method removes the changes you made
- This preserves existing data in other columns

### Drop Column Migration (ALTER)

When you remove columns from an existing table, the system generates a `Rocket::alter()` with `dropColumn()`.

```bash
php juice make:migration drop_old_column_from_products
```

```php
<?php
// migrations/m00004_drop_old_column_from_products.php

use Rocket\Migration\Migration;
use Rocket\Migration\Rocket;

class m00004_drop_old_column_from_products extends Migration
{
    public function up(): void
    {
        // Remove the column
        Rocket::alter('products', function($column) {
            $column->dropColumn('legacy_field');
        });
    }
    
    public function down(): void
    {
        // To restore the column, you need to know its definition
        // This is a placeholder - update with the actual column definition
        Rocket::alter('products', function($column) {
            $column->string('legacy_field');
        });
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

### Why This Matters: CREATE vs ALTER

Understanding the difference is crucial for database integrity:

| Operation | Method | When to Use |
|-----------|--------|-------------|
| **CREATE** | `Rocket::table()` | Only when the table doesn't exist yet |
| **ALTER** | `Rocket::alter()` | When the table already exists and has data |

**Example Scenario:**

```bash
# 1. First migration: Create the table
php juice make:migration create_products_table
# → Uses Rocket::table()

# 2. Later, need to add a column
php juice make:migration add_sku_to_products
# → Uses Rocket::alter() because table already exists

# 3. Later, need to remove a column
php juice make:migration drop_old_column_from_products
# → Uses Rocket::alter() with dropColumn()
```

### The Intelligence Behind It

The system doesn't just guess - it actively **analyzes your migration name**:

```php
// Inside the migration generator (simplified logic)
if (strpos($migrationName, 'create_') === 0 && strpos($migrationName, '_table') !== false) {
    // This is a table creation migration
    return $this->createTableTemplate($className, $tableName);
    
} elseif (strpos($migrationName, 'add_') === 0 && strpos($migrationName, '_to_') !== false) {
    // This adds columns to an existing table
    return $this->addColumnTemplate($className, $tableName, $column);
    
} elseif (strpos($migrationName, 'drop_') === 0 && strpos($migrationName, '_from_') !== false) {
    // This removes columns from an existing table
    return $this->dropColumnTemplate($className, $tableName, $column);
    
} elseif (strpos($migrationName, 'alter_') === 0) {
    // This is a general table alteration
    return $this->alterTableTemplate($className);
    
} else {
    // Generic fallback
    return $this->genericTemplate($className);
}
```

### Practical Example: Building a Products Table

Let's walk through a complete example:

```bash
# Step 1: Create the products table
php juice make:migration create_products_table
```

**Generated migration (CREATE):**
```php
Rocket::table('products', function($column) {
    $column->id('id');
    $column->string('name');
    $column->timestamps();
});
```

```bash
# Step 2: Run the migration
php juice db:migrate
# ✅ Table created

# Step 3: Add more columns later
php juice make:migration add_price_to_products
```

**Generated migration (ALTER):**
```php
Rocket::alter('products', function($column) {
    $column->decimal('price', 10, 2);
});
```

```bash
# Step 4: Run the new migration
php juice db:migrate
# ✅ Column added, existing data preserved

# Step 5: Add another column with constraints
php juice make:migration add_sku_to_products
```

**Generated migration (ALTER with constraints):**
```php
Rocket::alter('products', function($column) {
    $column->string('sku')->unique()->index();
});
```

```bash
# Step 6: Run the migration
php juice db:migrate
# ✅ Column added with unique constraint and index
```

### Complete Migration Example

Here's how a full migration history might look:

```php
// migrations/m00001_create_products_table.php (CREATE)
Rocket::table('products', function($column) {
    $column->id('id');
    $column->string('name');
    $column->timestamps();
});

// migrations/m00002_add_price_to_products.php (ALTER)
Rocket::alter('products', function($column) {
    $column->decimal('price', 10, 2);
});

// migrations/m00003_add_sku_to_products.php (ALTER)
Rocket::alter('products', function($column) {
    $column->string('sku')->unique()->index();
});

// migrations/m00004_add_description_to_products.php (ALTER)
Rocket::alter('products', function($column) {
    $column->text('description')->nullable();
});

// migrations/m00005_add_is_active_to_products.php (ALTER)
Rocket::alter('products', function($column) {
    $column->boolean('is_active')->default(true);
});
```

### The Result

After running all migrations, your `products` table will have all the columns you added over time:

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Added in m00001 (CREATE) |
| name | VARCHAR | Added in m00001 (CREATE) |
| price | DECIMAL | Added in m00002 (ALTER) |
| sku | VARCHAR | Added in m00003 (ALTER) |
| description | TEXT | Added in m00004 (ALTER) |
| is_active | BOOLEAN | Added in m00005 (ALTER) |
| created_at | TIMESTAMP | Added in m00001 (CREATE) |
| updated_at | TIMESTAMP | Added in m00001 (CREATE) |

**All existing data was preserved** because we used `ALTER` operations on an existing table instead of trying to recreate it!

### Best Practices for Migration Naming

1. **Create tables**: Use `create_{table}_table`
   ```bash
   php juice make:migration create_users_table
   php juice make:migration create_orders_table
   ```

2. **Add columns**: Use `add_{column}_to_{table}`
   ```bash
   php juice make:migration add_email_to_users
   php juice make:migration add_price_to_products
   ```

3. **Drop columns**: Use `drop_{column}_from_{table}`
   ```bash
   php juice make:migration drop_legacy_field_from_users
   ```

4. **Alter tables**: Use `alter_{table}`
   ```bash
   php juice make:migration alter_users_table
   ```

5. **Be descriptive**: The name should clearly indicate what the migration does
   ```bash
   # Good
   php juice make:migration add_phone_number_to_users
   php juice make:migration create_products_table
   
   # Avoid vague names
   php juice make:migration update
   php juice make:migration fix
   ```

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

### Seeder Structure

```php
<?php
namespace Seeds;

use Rocket\Seed\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create 10 regular users
        UserFactory::new()->count(10)->create();
        
        // Create an admin user
        UserFactory::new()->admin()->create();
        
        // Create 5 inactive users
        UserFactory::new()->count(5)->inactive()->create();
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

## Advanced Examples

### Complete Product Entity with Relationships

```php
<?php
namespace App\Entities;

use Rocket\ORM\Entity;
use Rocket\Attributes\Entity as EntityAttr;
use Rocket\Attributes\Column;
use Rocket\Attributes\Relations\HasMany;
use Rocket\Attributes\Relations\BelongsTo;
use Rocket\Attributes\Rules\Required;
use Rocket\Attributes\Rules\Min;
use Rocket\Attributes\Rules\Max;

#[EntityAttr(table: 'products')]
class Product extends Entity
{
    #[Column(primary: true, autoIncrement: true)]
    public int $id = 0;
    
    #[Column]
    #[Required]
    #[Min(3)]
    #[Max(255)]
    public string $name = '';
    
    #[Column]
    public string $description = '';
    
    #[Column]
    #[Required]
    #[Min(0)]
    public float $price = 0.00;
    
    #[Column]
    public int $stock = 0;
    
    #[Column]
    public bool $is_active = true;
    
    #[Column]
    public int $category_id = 0;
    
    #[Column(autoCreate: true)]
    public string $created_at = '';
    
    #[Column(autoCreate: true, autoUpdate: true)]
    public string $updated_at = '';
    
    #[BelongsTo(Category::class, 'category_id', 'id')]
    protected $category;
    
    #[HasMany(OrderItem::class, 'product_id', 'id')]
    protected $order_items;
    
    public function getDisplayName(): string
    {
        return $this->name;
    }
    
    public function isInStock(): bool
    {
        return $this->stock > 0;
    }
    
    public function getFormattedPrice(): string
    {
        return '$' . number_format($this->price, 2);
    }
    
    protected function beforeSave(): void
    {
        // Ensure price is always positive
        if ($this->price < 0) {
            $this->price = 0;
        }
        
        // Ensure stock is never negative
        if ($this->stock < 0) {
            $this->stock = 0;
        }
    }
}
```

## CLI Commands Reference

```bash
# Database
php juice db:create                # Create database
php juice db:migrate               # Run migrations
php juice db:rollback              # Rollback last batch
php juice db:reset                 # Rollback all migrations
php juice db:fresh                 # Drop all tables and re-migrate
php juice seed                     # Run seeders

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

4. **Use lifecycle hooks** for side effects like password hashing
   ```php
   protected function beforeSave(): void
   {
       if (!empty($this->password) && !$this->isPasswordHashed()) {
           $this->password = password_hash($this->password, PASSWORD_DEFAULT);
       }
   }
   ```

5. **Leverage relationships** to keep your code clean and expressive
   ```php
   $user = User::find(1);
   foreach ($user->posts as $post) {
       echo $post->title;
   }
   ```

6. **Use factories and seeders** for consistent test data
   ```bash
   php juice make:factory UserFactory
   php juice seed
   ```

7. **Keep migrations version-controlled** for team collaboration
   ```bash
   git add migrations/
   git commit -m "Add users table migration"
   ```

8. **Use query builder** for complex queries instead of raw SQL
   ```php
   $products = Product::query()
       ->where('price', '>', 100)
       ->where('stock', '>', 0)
       ->orderBy('name')
       ->limit(10)
       ->all();
   ```

9. **Use computed properties** for derived values
   ```php
   public function getTotalPrice(): float
   {
       return $this->quantity * $this->unit_price;
   }
   ```

10. **Use timestamps** for auditing and tracking
    ```php
    #[Column(autoCreate: true)]
    public string $created_at = '';
    
    #[Column(autoCreate: true, autoUpdate: true)]
    public string $updated_at = '';
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
```
