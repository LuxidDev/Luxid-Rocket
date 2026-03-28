<?php

namespace Seeds;

use Rocket\Seed\Factory;
use Rocket\Seed\Faker;
use Rocket\Tests\Post;

class PostFactory extends Factory
{
  protected static function getEntityClass(): string
  {
    return Post::class;
  }

  protected function definition(): array
  {
    return [
      'title' => Faker::sentence(),
      'content' => Faker::paragraphs(3, true),
      'user_id' => 1,
    ];
  }
}
