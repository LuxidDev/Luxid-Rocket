<?php

namespace Rocket\Attributes\Relations;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class BelongsTo
{
  protected string $relatedClass;
  protected ?string $foreignKey;
  protected ?string $ownerKey;

  public function __construct(string $relatedClass, ?string $foreignKey = null, ?string $ownerKey = null)
  {
    $this->relatedClass = $relatedClass;
    $this->foreignKey = $foreignKey;
    $this->ownerKey = $ownerKey;
  }

  public function getRelatedClass(): string
  {
    return $this->relatedClass;
  }

  public function getForeignKey(): ?string
  {
    return $this->foreignKey;
  }

  public function getOwnerKey(): ?string
  {
    return $this->ownerKey;
  }
}
