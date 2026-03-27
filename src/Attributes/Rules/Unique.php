<?php

namespace Rocket\Attributes\Rules;

use Attribute;
use Rocket\Connection\Connection;
use Rocket\ORM\Entity;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Unique
{
  protected ?string $table;
  protected string $message = 'This value has already been taken.';

  public function __construct(?string $table = null, ?string $message = null)
  {
    $this->table = $table;
    if ($message) {
      $this->message = $message;
    }
  }

  public function validate($value, Entity $entity): bool
  {
    if (empty($value)) {
      return true; // Use Required to enforce presence
    }

    $table = $this->table ?? $entity::tableName();
    $column = $this->getColumnName($entity);

    $query = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = :value";
    $params = [':value' => $value];

    // Exclude current record when updating
    if (!$entity->isNew) {
      $pk = $entity::primaryKey();
      $query .= " AND {$pk} != :id";
      $params[':id'] = $entity->$pk;
    }

    $result = Connection::getInstance()->query($query, $params);

    return ($result[0]['count'] ?? 0) === 0;
  }

  protected function getColumnName(Entity $entity): string
  {
    $reflection = new \ReflectionClass($entity);
    $metadata = $entity::getMetadata();

    // Find which property this rule is attached to
    foreach ($reflection->getProperties() as $property) {
      $attributes = $property->getAttributes(self::class);
      if (!empty($attributes)) {
        // Get the column name from metadata
        foreach ($metadata->getColumns() as $column) {
          if ($column->getProperty() === $property->getName()) {
            return $column->getName();
          }
        }
        return $property->getName();
      }
    }

    return 'id'; // Fallback
  }

  public function getMessage(): string
  {
    return $this->message;
  }
}
