<?php

namespace Rocket\Migration;

class Column
{
  protected string $name;
  protected string $type;
  protected array $options = [];

  public function __construct(string $name, string $type)
  {
    $this->name = $name;
    $this->type = $type;
  }

  public static function id(string $name = 'id'): self
  {
    $column = new self($name, 'INT');
    $column->options['primary'] = true;
    $column->options['auto_increment'] = true;
    return $column;
  }

  public static function string(string $name): self
  {
    return new self($name, 'VARCHAR(255)');
  }

  public static function text(string $name): self
  {
    return new self($name, 'TEXT');
  }

  public static function integer(string $name): self
  {
    return new self($name, 'INT');
  }

  public static function float(string $name): self
  {
    return new self($name, 'FLOAT');
  }

  public static function boolean(string $name): self
  {
    return new self($name, 'BOOLEAN');
  }

  public static function datetime(string $name): self
  {
    return new self($name, 'DATETIME');
  }

  public static function timestamps(): array
  {
    $created = new self('created_at', 'TIMESTAMP');
    $created->options['default'] = 'CURRENT_TIMESTAMP';

    $updated = new self('updated_at', 'TIMESTAMP');
    $updated->options['default'] = 'CURRENT_TIMESTAMP';
    $updated->options['on_update'] = 'CURRENT_TIMESTAMP';

    return [$created, $updated];
  }

  public static function softDeletes(): self
  {
    $column = new self('deleted_at', 'TIMESTAMP');
    $column->options['nullable'] = true;
    return $column;
  }

  public function unique(): self
  {
    $this->options['unique'] = true;
    return $this;
  }

  public function nullable(): self
  {
    $this->options['nullable'] = true;
    return $this;
  }

  public function default($value): self
  {
    $this->options['default'] = $value;
    return $this;
  }

  public function hidden(): self
  {
    $this->options['hidden'] = true;
    return $this;
  }

  public function index(): self
  {
    $this->options['index'] = true;
    return $this;
  }

  public function primary(): self
  {
    $this->options['primary'] = true;
    return $this;
  }

  public function autoIncrement(): self
  {
    $this->options['auto_increment'] = true;
    return $this;
  }

  public function getType(): string
  {
    return $this->type;
  }

  public function getName(): string
  {
    return $this->name;
  }

  public function getOptions(): array
  {
    return $this->options;
  }
}
