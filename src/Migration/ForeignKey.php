<?php

namespace Rocket\Migration;

class ForeignKey
{
  protected string $column;
  protected string $references;
  protected string $on;
  protected ?string $onDelete = null;
  protected ?string $onUpdate = null;

  public function __construct(string $column)
  {
    $this->column = $column;
  }

  public static function foreign(string $column): self
  {
    return new self($column);
  }

  public function references(string $column): self
  {
    $this->references = $column;
    return $this;
  }

  public function on(string $table): self
  {
    $this->on = $table;
    return $this;
  }

  public function cascadeOnDelete(): self
  {
    $this->onDelete = 'CASCADE';
    return $this;
  }

  public function setNullOnDelete(): self
  {
    $this->onDelete = 'SET NULL';
    return $this;
  }

  public function restrictOnDelete(): self
  {
    $this->onDelete = 'RESTRICT';
    return $this;
  }

  public function noActionOnDelete(): self
  {
    $this->onDelete = 'NO ACTION';
    return $this;
  }

  public function cascadeOnUpdate(): self
  {
    $this->onUpdate = 'CASCADE';
    return $this;
  }

  public function setNullOnUpdate(): self
  {
    $this->onUpdate = 'SET NULL';
    return $this;
  }

  public function getColumn(): string
  {
    return $this->column;
  }

  public function getReferences(): string
  {
    return $this->references;
  }

  public function getOn(): string
  {
    return $this->on;
  }

  public function getOnDelete(): ?string
  {
    return $this->onDelete;
  }

  public function getOnUpdate(): ?string
  {
    return $this->onUpdate;
  }
}
