<?php

namespace Rocket\Metadata;

class RelationMetadata
{
  protected string $name;
  protected string $type;
  protected string $relatedClass;
  protected ?string $foreignKey;
  protected ?string $localKey;
  protected ?string $ownerKey;

  public function __construct(
    string $name,
    string $type,
    string $relatedClass,
    ?string $foreignKey = null,
    ?string $localKey = null,
    ?string $ownerKey = null
  ) {
    $this->name = $name;
    $this->type = $type;
    $this->relatedClass = $relatedClass;
    $this->foreignKey = $foreignKey;
    $this->localKey = $localKey;
    $this->ownerKey = $ownerKey;
  }

  public function getName(): string
  {
    return $this->name;
  }

  public function getType(): string
  {
    return $this->type;
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

  public function getOwnerKey(): ?string
  {
    return $this->ownerKey;
  }
}
