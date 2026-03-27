<?php

namespace Rocket\Metadata;

class ColumnMetadata
{
  protected string $name = '';
  protected string $property = '';
  protected bool $primary = false;
  protected bool $autoIncrement = false;
  protected bool $nullable = false;
  protected $default = null;
  protected bool $hidden = false;
  protected bool $autoCreate = false;
  protected bool $autoUpdate = false;
  protected array $rules = [];

  public function getName(): string
  {
    return $this->name;
  }

  public function setName(string $name): self
  {
    $this->name = $name;
    return $this;
  }

  public function getProperty(): string
  {
    return $this->property;
  }

  public function setProperty(string $property): self
  {
    $this->property = $property;
    if (empty($this->name)) {
      $this->name = $property;
    }
    return $this;
  }

  public function isPrimary(): bool
  {
    return $this->primary;
  }

  public function setPrimary(bool $primary): self
  {
    $this->primary = $primary;
    return $this;
  }

  public function isAutoIncrement(): bool
  {
    return $this->autoIncrement;
  }

  public function setAutoIncrement(bool $autoIncrement): self
  {
    $this->autoIncrement = $autoIncrement;
    return $this;
  }

  public function isNullable(): bool
  {
    return $this->nullable;
  }

  public function setNullable(bool $nullable): self
  {
    $this->nullable = $nullable;
    return $this;
  }

  public function getDefault()
  {
    return $this->default;
  }

  public function setDefault($default): self
  {
    $this->default = $default;
    return $this;
  }

  public function isHidden(): bool
  {
    return $this->hidden;
  }

  public function setHidden(bool $hidden): self
  {
    $this->hidden = $hidden;
    return $this;
  }

  public function isAutoCreate(): bool
  {
    return $this->autoCreate;
  }

  public function setAutoCreate(bool $autoCreate): self
  {
    $this->autoCreate = $autoCreate;
    return $this;
  }

  public function isAutoUpdate(): bool
  {
    return $this->autoUpdate;
  }

  public function setAutoUpdate(bool $autoUpdate): self
  {
    $this->autoUpdate = $autoUpdate;
    return $this;
  }

  public function getRules(): array
  {
    return $this->rules;
  }

  public function addRule($rule): self
  {
    $this->rules[] = $rule;
    return $this;
  }
}
