<?php

namespace Rocket\Attributes\Relations;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class HasOne
{
  protected string $relatedClass;
  protected ?string $foreignKey;
  protected ?string $localKey;

  public function __construct(string $relatedClass, ?string $foreignKey = null, ?string $localKey = null)
  {
    $this->relatedClass = $relatedClass;
    $this->foreignKey = $foreignKey;
    $this->localKey = $localKey;
  }

  public function getRelatedClass(): string
  {
    return $this->relatedClass;
  }

  public function getForeignKey(): ?string
  {
    return $this->foreignKey;
  }

  public function getLocalKey(): ?string
  {
    return $this->localKey;
  }
}
