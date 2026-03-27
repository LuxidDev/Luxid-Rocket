<?php

namespace Rocket\Query;

use Rocket\Connection\Connection;
use Rocket\ORM\Entity;

class QueryBuilder
{
  protected string $entityClass;
  protected string $table;
  protected array $select = [];
  protected array $where = [];
  protected array $orderBy = [];
  protected ?int $limit = null;
  protected ?int $offset = null;
  protected array $bindings = [];

  public function __construct(string $entityClass)
  {
    $this->entityClass = $entityClass;
    $this->table = $entityClass::tableName();
    $this->select = ['*'];
  }

  public function select(array $columns): self
  {
    $this->select = $columns;
    return $this;
  }

  public function where(string $column, string $operator, $value): self
  {
    $param = 'where_' . count($this->bindings);
    $this->where[] = "{$column} {$operator} :{$param}";
    $this->bindings[$param] = $value;
    return $this;
  }

  public function whereIn(string $column, array $values): self
  {
    $placeholders = [];
    foreach ($values as $i => $value) {
      $param = "where_in_{$column}_{$i}";
      $placeholders[] = ":{$param}";
      $this->bindings[$param] = $value;
    }

    $this->where[] = "{$column} IN (" . implode(', ', $placeholders) . ")";
    return $this;
  }

  public function whereNull(string $column): self
  {
    $this->where[] = "{$column} IS NULL";
    return $this;
  }

  public function whereNotNull(string $column): self
  {
    $this->where[] = "{$column} IS NOT NULL";
    return $this;
  }

  public function orderBy(string $column, string $direction = 'ASC'): self
  {
    $this->orderBy[] = "{$column} {$direction}";
    return $this;
  }

  public function limit(int $limit): self
  {
    $this->limit = $limit;
    return $this;
  }

  public function offset(int $offset): self
  {
    $this->offset = $offset;
    return $this;
  }

  public function first(): ?Entity
  {
    $this->limit(1);
    $results = $this->all();
    return $results[0] ?? null;
  }

  public function all(): array
  {
    $sql = $this->buildSelect();
    $results = Connection::getInstance()->query($sql, $this->bindings);

    $entities = [];
    foreach ($results as $row) {
      $entity = new $this->entityClass();
      $entity->load($row);
      $entities[] = $entity;
    }

    return $entities;
  }

  public function count(): int
  {
    $sql = $this->buildCount();
    $result = Connection::getInstance()->query($sql, $this->bindings);
    return (int) ($result[0]['count'] ?? 0);
  }

  protected function buildSelect(): string
  {
    $select = implode(', ', $this->select);
    $sql = "SELECT {$select} FROM {$this->table}";

    if (!empty($this->where)) {
      $sql .= " WHERE " . implode(' AND ', $this->where);
    }

    if (!empty($this->orderBy)) {
      $sql .= " ORDER BY " . implode(', ', $this->orderBy);
    }

    if ($this->limit !== null) {
      $sql .= " LIMIT {$this->limit}";
    }

    if ($this->offset !== null) {
      $sql .= " OFFSET {$this->offset}";
    }

    return $sql;
  }

  protected function buildCount(): string
  {
    $sql = "SELECT COUNT(*) as count FROM {$this->table}";

    if (!empty($this->where)) {
      $sql .= " WHERE " . implode(' AND ', $this->where);
    }

    return $sql;
  }
}
