<?php

namespace Seeds;

use Rocket\Seed\Factory;
use Rocket\Seed\Faker;
use Rocket\Tests\Profile;

class ProfileFactory extends Factory
{
  protected static function getEntityClass(): string
  {
    return Profile::class;
  }

  protected function definition(): array
  {
    return [
      'bio' => Faker::paragraph(),
      'avatar' => Faker::imageUrl(200, 200, 'people'),
      'user_id' => 1,
    ];
  }
}
