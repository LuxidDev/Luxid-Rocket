<?php

namespace Rocket\Seed;

class Faker
{
  protected static ?\Faker\Generator $instance = null;

  public static function instance(): \Faker\Generator
  {
    if (self::$instance === null) {
      self::$instance = \Faker\Factory::create();
    }
    return self::$instance;
  }

  public static function __callStatic(string $method, array $arguments)
  {
    return self::instance()->$method(...$arguments);
  }
}
