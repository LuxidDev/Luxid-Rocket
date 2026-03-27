<?php

namespace Rocket\Attributes;

use Attribute;
use Rocket\Metadata\ColumnMetadata;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
  public ?string $name = null;
  public bool $primary = false;
  public bool $autoIncrement = false;
  public bool $nullable = false;
  public $default = null;
  public bool $hidden = false;
  public bool $autoCreate = false;
  public bool $autoUpdate = false;

  public function __construct(
    ?string $name = null,
    bool $primary = false,
    bool $autoIncrement = false,
    bool $nullable = false,
    $default = null,
    bool $hidden = false,
    bool $autoCreate = false,
    bool $autoUpdate = false
  ) {
    $this->name = $name;
    $this->primary = $primary;
    $this->autoIncrement = $autoIncrement;
    $this->nullable = $nullable;
    $this->default = $default;
    $this->hidden = $hidden;
    $this->autoCreate = $autoCreate;
    $this->autoUpdate = $autoUpdate;
  }

  public function configure(ColumnMetadata $metadata): void
  {
    if ($this->name) {
      $metadata->setName($this->name);
    }

    $metadata->setPrimary($this->primary)
      ->setAutoIncrement($this->autoIncrement)
      ->setNullable($this->nullable)
      ->setDefault($this->default)
      ->setHidden($this->hidden)
      ->setAutoCreate($this->autoCreate)
      ->setAutoUpdate($this->autoUpdate);
  }
}
