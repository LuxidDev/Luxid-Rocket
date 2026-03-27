<?php

namespace Rocket\Migration;

use Rocket\Connection\Connection;

class Schema
{
  protected Connection $db;
  protected string $table;
  protected array $columns = [];
  protected array $foreignKeys = [];
  protected array $indexes = [];

  public function __construct(Connection $db, string $table)
  {
    $this->db = $db;
    $this->table = $table;
  }

  /**
   * Add an auto-incrementing ID column
   */
  public function id(string $name = 'id'): Column
  {
    $column = Column::id($name);
    $this->columns[] = $column;
    return $column;
  }

  /**
   * Add a string column
   */
  public function string(string $name): Column
  {
    $column = Column::string($name);
    $this->columns[] = $column;
    return $column;
  }

  /**
   * Add a text column
   */
  public function text(string $name): Column
  {
    $column = Column::text($name);
    $this->columns[] = $column;
    return $column;
  }

  /**
   * Add an integer column
   */
  public function integer(string $name): Column
  {
    $column = Column::integer($name);
    $this->columns[] = $column;
    return $column;
  }

  /**
   * Add a float column
   */
  public function float(string $name): Column
  {
    $column = Column::float($name);
    $this->columns[] = $column;
    return $column;
  }

  /**
   * Add a boolean column
   */
  public function boolean(string $name): Column
  {
    $column = Column::boolean($name);
    $this->columns[] = $column;
    return $column;
  }

  /**
   * Add timestamp columns (created_at, updated_at)
   */
  public function timestamps(): void
  {
    $timestamps = Column::timestamps();
    foreach ($timestamps as $column) {
      $this->columns[] = $column;
    }
  }

  /**
   * Add soft deletes column
   */
  public function softDeletes(): Column
  {
    $column = Column::softDeletes();
    $this->columns[] = $column;
    return $column;
  }

  /**
   * Add a foreign key constraint
   */
  public function foreign(string $column): ForeignKey
  {
    $foreignKey = new ForeignKey($column);
    $this->foreignKeys[] = $foreignKey;
    return $foreignKey;
  }

  public function create(): void
  {
    $sql = "CREATE TABLE {$this->table} (\n";

    // Add columns
    $columnDefs = [];
    foreach ($this->columns as $column) {
      $columnDefs[] = $this->buildColumnDefinition($column);
    }

    // Add primary keys
    $primaryKeys = $this->getPrimaryKeys();
    if (!empty($primaryKeys)) {
      $columnDefs[] = "PRIMARY KEY (" . implode(', ', $primaryKeys) . ")";
    }

    // Add unique constraints
    $uniqueConstraints = $this->getUniqueConstraints();
    foreach ($uniqueConstraints as $constraint) {
      $columnDefs[] = "UNIQUE KEY {$constraint['name']} ({$constraint['columns']})";
    }

    // Add indexes
    $indexes = $this->getIndexes();
    foreach ($indexes as $index) {
      $columnDefs[] = "INDEX {$index['name']} ({$index['columns']})";
    }

    $sql .= implode(",\n", $columnDefs);
    $sql .= "\n)";

    $this->db->execute($sql);

    // Add foreign keys after table creation
    foreach ($this->foreignKeys as $fk) {
      $this->addForeignKeyConstraint($fk);
    }
  }

  protected function buildColumnDefinition(Column $column): string
  {
    $def = "{$column->getName()} {$column->getType()}";
    $options = $column->getOptions();

    if (isset($options['nullable']) && $options['nullable']) {
      $def .= " NULL";
    } else {
      $def .= " NOT NULL";
    }

    if (isset($options['default'])) {
      if ($options['default'] === 'CURRENT_TIMESTAMP') {
        $def .= " DEFAULT CURRENT_TIMESTAMP";
      } else {
        $def .= " DEFAULT '{$options['default']}'";
      }
    }

    if (isset($options['auto_increment']) && $options['auto_increment']) {
      $def .= " AUTO_INCREMENT";
    }

    return $def;
  }

  protected function getPrimaryKeys(): array
  {
    $primaryKeys = [];
    foreach ($this->columns as $column) {
      $options = $column->getOptions();
      if (isset($options['primary']) && $options['primary']) {
        $primaryKeys[] = $column->getName();
      }
    }
    return $primaryKeys;
  }

  protected function getUniqueConstraints(): array
  {
    $unique = [];
    $i = 0;
    foreach ($this->columns as $column) {
      $options = $column->getOptions();
      if (isset($options['unique']) && $options['unique']) {
        $i++;
        $unique[] = [
          'name' => "{$this->table}_{$column->getName()}_unique",
          'columns' => $column->getName()
        ];
      }
    }
    return $unique;
  }

  protected function getIndexes(): array
  {
    $indexes = [];
    $i = 0;
    foreach ($this->columns as $column) {
      $options = $column->getOptions();
      if (isset($options['index']) && $options['index']) {
        $i++;
        $indexes[] = [
          'name' => "{$this->table}_{$column->getName()}_index",
          'columns' => $column->getName()
        ];
      }
    }
    return $indexes;
  }

  protected function addForeignKeyConstraint(ForeignKey $fk): void
  {
    $constraintName = "fk_{$this->table}_{$fk->getColumn()}";
    $sql = "ALTER TABLE {$this->table} ADD CONSTRAINT {$constraintName} ";
    $sql .= "FOREIGN KEY ({$fk->getColumn()}) REFERENCES {$fk->getOn()}({$fk->getReferences()})";

    if ($fk->getOnDelete()) {
      $sql .= " ON DELETE {$fk->getOnDelete()}";
    }

    if ($fk->getOnUpdate()) {
      $sql .= " ON UPDATE {$fk->getOnUpdate()}";
    }

    $this->db->execute($sql);
  }
}
