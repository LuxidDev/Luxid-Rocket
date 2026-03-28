<?php

namespace Seeds;

use Rocket\Seed\Factory;
use Rocket\Seed\Faker;
use Rocket\Tests\User;

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
    ];
  }

  public function admin(): self
  {
    echo "Admin method called\n";
    return $this->state([
      'name' => 'Admin User',
      'email' => 'admin@example.com',
    ]);
  }
}
