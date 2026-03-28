<?php

namespace Rocket\Seed;

use Rocket\Connection\Connection;

class SeederRunner
{
  protected Connection $db;
  protected string $seedersPath;

  public function __construct(Connection $db, string $seedersPath)
  {
    $this->db = $db;
    $this->seedersPath = rtrim($seedersPath, '/');
  }

  public function run(string $seederClass = null): void
  {
    if ($seederClass) {
      $this->runSeeder($seederClass);
    } else {
      $this->runAll();
    }
  }

  protected function runAll(): void
  {
    $files = $this->getSeederFiles();

    // DatabaseSeeder should run last if it exists
    $databaseSeeder = null;
    $otherSeeders = [];

    foreach ($files as $file) {
      $className = $this->getClassNameFromFile($file);

      // Check if this class extends Seeder
      require_once $file;
      $fullClassName = "Seeds\\{$className}";

      if (class_exists($fullClassName) && is_subclass_of($fullClassName, Seeder::class)) {
        if ($className === 'DatabaseSeeder') {
          $databaseSeeder = $file;
        } else {
          $otherSeeders[] = $file;
        }
      }
    }

    // Run other seeders first
    foreach ($otherSeeders as $file) {
      $this->runSeederFile($file);
    }

    // Run DatabaseSeeder last
    if ($databaseSeeder) {
      $this->runSeederFile($databaseSeeder);
    }
  }

  protected function runSeeder(string $seederClass): void
  {
    $file = $this->findSeederFile($seederClass);
    if ($file) {
      $this->runSeederFile($file);
    } else {
      echo "❌ Seeder not found: {$seederClass}\n";
    }
  }

  protected function runSeederFile(string $file): void
  {
    require_once $file;

    // Get the class name without namespace
    $className = $this->getClassNameFromFile($file);
    $fullClassName = "Seeds\\{$className}";

    if (!class_exists($fullClassName)) {
      echo "❌ Class not found: {$fullClassName}\n";
      return;
    }

    // Only run if it's a Seeder class
    if (!is_subclass_of($fullClassName, Seeder::class)) {
      // Skip factory classes silently
      return;
    }

    $seeder = new $fullClassName();
    $seeder->run();
  }

  protected function getSeederFiles(): array
  {
    $files = glob($this->seedersPath . '/*.php');
    sort($files);
    return $files;
  }

  protected function findSeederFile(string $className): ?string
  {
    $file = $this->seedersPath . '/' . $className . '.php';
    if (file_exists($file)) {
      return $file;
    }
    return null;
  }

  protected function getClassNameFromFile(string $file): string
  {
    return pathinfo($file, PATHINFO_FILENAME);
  }
}
