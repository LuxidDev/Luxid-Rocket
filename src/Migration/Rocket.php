<?php

namespace Rocket\Migration;

use Rocket\Connection\Connection;

class Rocket
{
  protected static ?Connection $connection = null;
  protected static ?Schema $currentSchema = null;

  public static function setConnection(Connection $connection): void
  {
    self::$connection = $connection;
  }

  public static function table(string $table, callable $callback): void
  {
    $schema = new Schema(self::$connection, $table);
    self::$currentSchema = $schema;
    $callback($schema);
    $schema->create();
    self::$currentSchema = null;
  }

  public static function drop(string $table): void
  {
    $sql = "DROP TABLE IF EXISTS {$table}";
    self::$connection->execute($sql);
  }

  public static function rename(string $from, string $to): void
  {
    $sql = "RENAME TABLE {$from} TO {$to}";
    self::$connection->execute($sql);
  }

  public static function hasTable(string $table): bool
  {
    $result = self::$connection->query("SHOW TABLES LIKE '{$table}'");
    return !empty($result);
  }

  public static function foreign(string $column): ForeignKey
  {
    if (self::$currentSchema === null) {
      throw new \RuntimeException('foreign() must be called within a table definition');
    }

    return self::$currentSchema->foreign($column);
  }
}
