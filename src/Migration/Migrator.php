<?php

namespace Rocket\Migration;

use Rocket\Connection\Connection;

class Migrator
{
  protected Connection $db;
  protected string $migrationsPath;
  protected string $table = 'migrations';

  public function __construct(Connection $db, string $migrationsPath)
  {
    $this->db = $db;
    $this->migrationsPath = rtrim($migrationsPath, '/');
    $this->ensureMigrationsTable();
  }

  protected function ensureMigrationsTable(): void
  {
    $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

    $this->db->execute($sql);
  }

  public function run(): void
  {
    $executed = $this->getExecutedMigrations();
    $files = $this->getMigrationFiles();
    $batch = $this->getNextBatch();

    $newMigrations = [];

    foreach ($files as $file) {
      $name = pathinfo($file, PATHINFO_FILENAME);

      if (!in_array($name, $executed)) {
        $this->runMigration($file, $name);
        $newMigrations[] = $name;
        echo "Migrated: {$name}\n";
      }
    }

    if (!empty($newMigrations)) {
      $this->recordMigrations($newMigrations, $batch);
      echo "All migrations completed!\n";
    } else {
      echo "Nothing to migrate.\n";
    }
  }

  public function rollback(int $steps = 1): void
  {
    $executed = $this->getExecutedMigrationsWithBatch();
    $batches = array_unique(array_column($executed, 'batch'));
    rsort($batches);

    $batchesToRollback = array_slice($batches, 0, $steps);

    foreach ($batchesToRollback as $batch) {
      $migrations = array_filter($executed, fn($m) => $m['batch'] === $batch);

      foreach (array_reverse($migrations) as $migration) {
        $this->rollbackMigration($migration['migration']);
        $this->removeMigration($migration['migration']);
        echo "Rolled back: {$migration['migration']}\n";
      }
    }

    echo "Rollback completed!\n";
  }

  public function reset(): void
  {
    $executed = $this->getExecutedMigrations();

    foreach (array_reverse($executed) as $migration) {
      $this->rollbackMigration($migration);
      $this->removeMigration($migration);
      echo "Rolled back: {$migration}\n";
    }

    echo "All migrations rolled back!\n";
  }

  public function fresh(): void
  {
    $this->reset();
    $this->run();
  }

  protected function runMigration(string $file, string $name): void
  {
    require_once $file;
    $migration = new $name();
    $migration->up();
  }

  protected function rollbackMigration(string $name): void
  {
    $file = $this->findMigrationFile($name);
    if ($file) {
      require_once $file;
      $migration = new $name();
      $migration->down();
    }
  }

  protected function getMigrationFiles(): array
  {
    $files = glob($this->migrationsPath . '/m*.php');
    sort($files);
    return $files;
  }

  protected function findMigrationFile(string $name): ?string
  {
    $pattern = $this->migrationsPath . '/' . $name . '.php';
    if (file_exists($pattern)) {
      return $pattern;
    }

    $files = glob($this->migrationsPath . '/m*_' . $name . '.php');
    return $files[0] ?? null;
  }

  protected function getExecutedMigrations(): array
  {
    $result = $this->db->query("SELECT migration FROM {$this->table} ORDER BY id");
    return array_column($result, 'migration');
  }

  protected function getExecutedMigrationsWithBatch(): array
  {
    return $this->db->query("SELECT migration, batch FROM {$this->table} ORDER BY id");
  }

  protected function getNextBatch(): int
  {
    $result = $this->db->query("SELECT MAX(batch) as max FROM {$this->table}");
    return ($result[0]['max'] ?? 0) + 1;
  }

  protected function recordMigrations(array $migrations, int $batch): void
  {
    foreach ($migrations as $migration) {
      $sql = "INSERT INTO {$this->table} (migration, batch) VALUES (:migration, :batch)";
      $this->db->execute($sql, [':migration' => $migration, ':batch' => $batch]);
    }
  }

  protected function removeMigration(string $migration): void
  {
    $sql = "DELETE FROM {$this->table} WHERE migration = :migration";
    $this->db->execute($sql, [':migration' => $migration]);
  }
}
