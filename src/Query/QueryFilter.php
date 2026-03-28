<?php

namespace Rocket\Query;

use Rocket\Query\QueryBuilder;

class QueryFilter
{
  /**
   * Apply filters to a query builder
   * 
   * @param QueryBuilder $query
   * @param array $filters
   * @param array $params Query parameters (e.g., from $_GET or Request::query())
   * @return QueryBuilder
   */
  public static function apply(QueryBuilder $query, array $filters, array $params): QueryBuilder
  {
    foreach ($filters as $param => $config) {
      $value = $params[$param] ?? null;

      // Skip if value is empty
      if ($value === null || $value === '') {
        continue;
      }

      // Validate against allowed values if specified
      if (isset($config['values']) && !in_array($value, $config['values'])) {
        continue;
      }

      $operator = $config['operator'] ?? '=';
      $columns = (array) $config['column'];

      // Handle multi-column search with OR conditions
      if (count($columns) > 1 && $operator === 'LIKE') {
        $query->where(function ($q) use ($columns, $value) {
          foreach ($columns as $column) {
            $q->orWhere($column, 'LIKE', "%{$value}%");
          }
        });
      } else {
        $column = $columns[0];
        if ($operator === 'LIKE') {
          $query->where($column, 'LIKE', "%{$value}%");
        } else {
          $query->where($column, $operator, $value);
        }
      }
    }

    return $query;
  }

  /**
   * Apply pagination to a query builder
   * 
   * @param QueryBuilder $query
   * @param array $params Query parameters (e.g., from $_GET or Request::query())
   * @return QueryBuilder
   */
  public static function paginate(QueryBuilder $query, array $params): QueryBuilder
  {
    $limit = (int) ($params['limit'] ?? 0);
    $offset = (int) ($params['offset'] ?? 0);

    if ($limit > 0) {
      $query->limit($limit)->offset($offset);
    }

    return $query;
  }

  /**
   * Apply ordering to a query builder
   * 
   * @param QueryBuilder $query
   * @param array $orderBy
   * @return QueryBuilder
   */
  public static function orderBy(QueryBuilder $query, array $orderBy): QueryBuilder
  {
    foreach ($orderBy as $column => $direction) {
      $query->orderBy($column, $direction);
    }

    return $query;
  }

  /**
   * Get all query parameters from filters
   * 
   * @param array $filters
   * @param array $params Query parameters
   * @return array
   */
  public static function getParams(array $filters, array $params): array
  {
    $result = [];

    foreach ($filters as $param => $config) {
      $result[$param] = $params[$param] ?? null;
    }

    $result['limit'] = (int) ($params['limit'] ?? 0);
    $result['offset'] = (int) ($params['offset'] ?? 0);

    return $result;
  }
}
