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
  protected array $orWhere = [];
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

  /**
   * WHERE condition
   * 
   * Supports:
   * - Simple: where('status', '=', 'pending')
   * - Nested: where(function($q) { $q->where(...)->orWhere(...); })
   */
  public function where($column, $operator = null, $value = null): self
  {
    // Handle closure for nested conditions
    if ($column instanceof \Closure) {
      $subQuery = new self($this->entityClass);
      $column($subQuery);

      // Get the subquery conditions
      $subWhere = $subQuery->getWhereConditions();
      $subOrWhere = $subQuery->getOrWhereConditions();

      // Merge bindings
      $this->bindings = array_merge($this->bindings, $subQuery->getBindings());

      // Build the nested condition
      if (!empty($subWhere) && !empty($subOrWhere)) {
        $this->where[] = '(' . implode(' AND ', $subWhere) . ' AND (' . implode(' OR ', $subOrWhere) . '))';
      } elseif (!empty($subWhere)) {
        $this->where[] = '(' . implode(' AND ', $subWhere) . ')';
      } elseif (!empty($subOrWhere)) {
        $this->where[] = '(' . implode(' OR ', $subOrWhere) . ')';
      }

      return $this;
    }

    // Handle simple where clause
    if (func_num_args() === 2) {
      $value = $operator;
      $operator = '=';
    }

    $param = 'where_' . count($this->bindings);
    $this->where[] = "{$column} {$operator} :{$param}";
    $this->bindings[$param] = $value;
    return $this;
  }

  /**
   * OR WHERE condition
   * 
   * Supports:
   * - Simple: orWhere('status', '=', 'completed')
   * - Nested: orWhere(function($q) { $q->where(...)->orWhere(...); })
   */
  public function orWhere($column, $operator = null, $value = null): self
  {
    // Handle closure for nested conditions
    if ($column instanceof \Closure) {
      $subQuery = new self($this->entityClass);
      $column($subQuery);

      // Get the subquery conditions
      $subWhere = $subQuery->getWhereConditions();
      $subOrWhere = $subQuery->getOrWhereConditions();

      // Merge bindings
      $this->bindings = array_merge($this->bindings, $subQuery->getBindings());

      // Build the nested condition
      if (!empty($subWhere) && !empty($subOrWhere)) {
        $this->orWhere[] = '(' . implode(' AND ', $subWhere) . ' AND (' . implode(' OR ', $subOrWhere) . '))';
      } elseif (!empty($subWhere)) {
        $this->orWhere[] = '(' . implode(' AND ', $subWhere) . ')';
      } elseif (!empty($subOrWhere)) {
        $this->orWhere[] = '(' . implode(' OR ', $subOrWhere) . ')';
      }

      return $this;
    }

    // Handle simple orWhere clause
    if (func_num_args() === 2) {
      $value = $operator;
      $operator = '=';
    }

    $param = 'or_where_' . count($this->bindings);
    $this->orWhere[] = "{$column} {$operator} :{$param}";
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

  /**
   * Get the WHERE conditions (for nested queries)
   */
  public function getWhereConditions(): array
  {
    return $this->where;
  }

  /**
   * Get the OR WHERE conditions (for nested queries)
   */
  public function getOrWhereConditions(): array
  {
    return $this->orWhere;
  }

  /**
   * Get all bindings (for nested queries)
   */
  public function getBindings(): array
  {
    return $this->bindings;
  }

  protected function buildSelect(): string
  {
    $select = implode(', ', $this->select);
    $sql = "SELECT {$select} FROM {$this->table}";

    $whereClause = $this->buildWhereClause();
    if ($whereClause) {
      $sql .= " WHERE {$whereClause}";
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

  protected function buildWhereClause(): string
  {
    $conditions = [];

    // Add AND conditions
    if (!empty($this->where)) {
      $conditions[] = implode(' AND ', $this->where);
    }

    // Add OR conditions
    if (!empty($this->orWhere)) {
      $orClause = implode(' OR ', $this->orWhere);
      if (!empty($conditions)) {
        $conditions[] = "({$orClause})";
      } else {
        $conditions[] = $orClause;
      }
    }

    return implode(' AND ', $conditions);
  }

  protected function buildCount(): string
  {
    $sql = "SELECT COUNT(*) as count FROM {$this->table}";

    $whereClause = $this->buildWhereClause();
    if ($whereClause) {
      $sql .= " WHERE {$whereClause}";
    }

    return $sql;
  }
}
