<?php

namespace Rocket\Console;

use Rocket\Connection\Connection;
use Rocket\Migration\Migrator;

class MigrateCommand
{
  protected Connection $db;
  protected string $migrationsPath;

  public function __construct(Connection $db, string $projectRoot)
  {
    $this->db = $db;
    $this->migrationsPath = $projectRoot . '/migrations';
  }

  public function handle(array $args): int
  {
    $command = $args[0] ?? 'migrate';

    $migrator = new Migrator($this->db, $this->migrationsPath);

    switch ($command) {
      case 'migrate':
      case 'up':
        $migrator->run();
        break;

      case 'rollback':
        $steps = $args[1] ?? 1;
        $migrator->rollback((int) $steps);
        break;

      case 'reset':
        $migrator->reset();
        break;

      case 'fresh':
        $migrator->fresh();
        break;

      default:
        echo "Unknown command: {$command}\n";
        echo "Available: migrate, rollback, reset, fresh\n";
        return 1;
    }

    return 0;
  }
}
