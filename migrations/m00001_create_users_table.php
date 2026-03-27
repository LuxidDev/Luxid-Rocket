<?php

use Rocket\Migration\Migration;
use Rocket\Migration\Rocket;

class m00001_create_users_table extends Migration
{
  public function up(): void
  {
    Rocket::table('users', function ($column) {
      $column->id('id');
      $column->string('email')->unique();
      $column->string('password')->hidden();
      $column->string('firstname');
      $column->string('lastname');
      $column->string('middle_name')->nullable();
      $column->boolean('is_active')->default(true);
      $column->string('username')->unique();
      $column->integer('age')->index();

      // Add role_id column FIRST
      $column->integer('role_id')->nullable();

      $column->timestamps();

      // THEN add foreign key constraint
      $column->foreign('role_id')
        ->references('id')
        ->on('roles')
        ->setNullOnDelete();
    });

    echo "✅ Created users table with foreign key\n";
  }

  public function down(): void
  {
    Rocket::drop('users');
    echo "✅ Dropped users table\n";
  }
}
