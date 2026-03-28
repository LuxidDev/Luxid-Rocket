<?php

namespace Seeds;

use Rocket\Seed\Seeder;

class DatabaseSeeder extends Seeder
{
  public function run(): void
  {
    echo "🌱 Seeding database...\n";

    // Create admin user using state instead of admin method
    $admin = UserFactory::new()
      ->state(['name' => 'Admin User', 'email' => 'admin@example.com'])
      ->create();

    if ($admin) {
      echo "  ✓ Created admin user: {$admin->name} ({$admin->email})\n";
    } else {
      echo "  ✗ Failed to create admin user\n";
    }

    // Create 10 regular users
    $users = UserFactory::new()->count(10)->create();
    echo "  ✓ Created 10 regular users\n";

    // Create profile for each user
    foreach ($users as $user) {
      ProfileFactory::new()
        ->state(['user_id' => $user->id])
        ->create();
    }
    echo "  ✓ Created profiles for all users\n";

    // Create posts for each user (2-5 posts per user)
    foreach ($users as $user) {
      $postCount = rand(2, 5);
      PostFactory::new()
        ->count($postCount)
        ->state(['user_id' => $user->id])
        ->create();
    }
    echo "  ✓ Created posts for all users\n";

    echo "✅ Database seeding completed!\n";
  }
}
