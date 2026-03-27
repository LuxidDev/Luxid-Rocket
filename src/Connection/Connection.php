<?php

namespace Rocket\Connection;

use PDO;
use PDOException;

class Connection
{
  protected static ?Connection $instance = null;
  protected PDO $pdo;
  protected array $config;

  protected function __construct(array $config)
  {
    $this->config = $config;
    $this->connect();
  }

  public static function getInstance(?array $config = null): self
  {
    if (self::$instance === null) {
      if ($config === null) {
        throw new \RuntimeException('Connection not initialized. Call initialize() first.');
      }
      self::$instance = new self($config);
    }

    return self::$instance;
  }

  public static function initialize(array $config): void
  {
    self::$instance = new self($config);
  }

  protected function connect(): void
  {
    $dsn = $this->config['dsn'] ?? '';
    $username = $this->config['user'] ?? '';
    $password = $this->config['password'] ?? '';

    try {
      $this->pdo = new PDO($dsn, $username, $password);
      $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
    }
  }

  public function getPdo(): PDO
  {
    return $this->pdo;
  }

  public function insert(string $table, array $data): bool
  {
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));

    $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

    $stmt = $this->pdo->prepare($sql);

    foreach ($data as $key => $value) {
      $stmt->bindValue(':' . $key, $value);
    }

    return $stmt->execute();
  }

  public function update(string $table, array $data, array $where): bool
  {
    $set = [];
    foreach (array_keys($data) as $column) {
      $set[] = "{$column} = :{$column}";
    }

    $whereClause = [];
    foreach (array_keys($where) as $column) {
      $whereClause[] = "{$column} = :where_{$column}";
    }

    $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE " . implode(' AND ', $whereClause);

    $stmt = $this->pdo->prepare($sql);

    foreach ($data as $key => $value) {
      $stmt->bindValue(':' . $key, $value);
    }

    foreach ($where as $key => $value) {
      $stmt->bindValue(':where_' . $key, $value);
    }

    return $stmt->execute();
  }

  public function delete(string $table, array $where): bool
  {
    $whereClause = [];
    foreach (array_keys($where) as $column) {
      $whereClause[] = "{$column} = :{$column}";
    }

    $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereClause);

    $stmt = $this->pdo->prepare($sql);

    foreach ($where as $key => $value) {
      $stmt->bindValue(':' . $key, $value);
    }

    return $stmt->execute();
  }

  public function query(string $sql, array $params = []): array
  {
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
  }

  public function execute(string $sql, array $params = []): int
  {
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
  }

  public function lastInsertId(): int
  {
    return (int) $this->pdo->lastInsertId();
  }

  public function beginTransaction(): bool
  {
    return $this->pdo->beginTransaction();
  }

  public function commit(): bool
  {
    return $this->pdo->commit();
  }

  public function rollback(): bool
  {
    return $this->pdo->rollBack();
  }
}
