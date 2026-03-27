<?php

use Rocket\Migration\Migration;
use Rocket\Migration\Rocket;

class m00000_create_roles_table extends Migration
{
  public function up(): void
  {
    Rocket::table('roles', function ($column) {
      $column->id('id');
      $column->string('name')->unique();
      $column->text('description')->nullable();
      $column->timestamps();
    });

    echo "✅ Created roles table\n";
  }

  public function down(): void
  {
    Rocket::drop('roles');
    echo "✅ Dropped roles table\n";
  }
}
