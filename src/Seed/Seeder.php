<?php

namespace Rocket\Seed;

use Rocket\Connection\Connection;

abstract class Seeder
{
  protected Connection $db;

  public function __construct()
  {
    $this->db = Connection::getInstance();
  }

  abstract public function run(): void;

  protected function call(string $seederClass): void
  {
    $seeder = new $seederClass();
    $seeder->run();
    echo "  ✓ Seeded: " . basename(str_replace('\\', '/', $seederClass)) . "\n";
  }

  protected function getConnection(): Connection
  {
    return $this->db;
  }
}
