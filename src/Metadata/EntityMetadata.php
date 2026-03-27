<?php

namespace Rocket\Metadata;

use Rocket\Attributes\Column;
use Rocket\Attributes\Entity as EntityAttribute;
use ReflectionClass;

class EntityMetadata
{
  protected string $className;
  protected string $tableName;
  protected string $primaryKey = 'id';
  protected array $columns = [];
  protected array $relations = [];
  protected bool $hasAutoIncrement = false;

  public function __construct(string $className)
  {
    $this->className = $className;
    $this->parseAttributes();
  }

  protected function parseAttributes(): void
  {
    $reflection = new ReflectionClass($this->className);

    // Parse entity attribute
    $entityAttributes = $reflection->getAttributes(EntityAttribute::class);
    if (!empty($entityAttributes)) {
      $entityAttribute = $entityAttributes[0]->newInstance();
      $this->tableName = $entityAttribute->getTable();
    } else {
      // Default table name from class name
      $this->tableName = strtolower($reflection->getShortName()) . 's';
    }

    // Parse properties
    foreach ($reflection->getProperties() as $property) {
      $columnAttributes = $property->getAttributes(Column::class);
      if (!empty($columnAttributes)) {
        // Create ColumnMetadata
        $columnMetadata = new ColumnMetadata();
        $columnMetadata->setProperty($property->getName());

        // Configure from Column attribute
        $columnAttr = $columnAttributes[0]->newInstance();
        $columnAttr->configure($columnMetadata);

        // Parse validation rules
        $this->parseValidationRules($property, $columnMetadata);

        $this->columns[] = $columnMetadata;

        if ($columnMetadata->isPrimary()) {
          $this->primaryKey = $columnMetadata->getName();
        }

        if ($columnMetadata->isAutoIncrement()) {
          $this->hasAutoIncrement = true;
        }
      }
    }
  }

  protected function parseValidationRules(\ReflectionProperty $property, ColumnMetadata $columnMetadata): void
  {
    $attributes = $property->getAttributes();

    foreach ($attributes as $attribute) {
      $attributeName = $attribute->getName();

      // Check if it's a validation rule
      if (strpos($attributeName, 'Rocket\\Attributes\\Rules\\') === 0) {
        $rule = $attribute->newInstance();
        $columnMetadata->addRule($rule);
      }
    }
  }

  public function getTableName(): string
  {
    return $this->tableName;
  }

  public function getPrimaryKey(): string
  {
    return $this->primaryKey;
  }

  public function getColumns(): array
  {
    return $this->columns;
  }

  public function getRelations(): array
  {
    return $this->relations;
  }

  public function hasAutoIncrement(): bool
  {
    return $this->hasAutoIncrement;
  }
}
