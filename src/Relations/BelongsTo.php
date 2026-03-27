<?php

namespace Rocket\Relations;

use Rocket\ORM\Entity;

class BelongsTo extends Relation
{
  public function __construct(Entity $parent, string $relatedClass, string $foreignKey, string $ownerKey)
  {
    parent::__construct($parent, $relatedClass, $foreignKey, $ownerKey);
    $this->addConstraints();
  }

  public function addConstraints(): void
  {
    // No constraints needed for lazy loading
  }

  public function getResults(): ?Entity
  {
    $query = $this->getRelatedQuery();
    $query->where($this->localKey, '=', $this->parent->{$this->foreignKey});

    return $query->first();
  }

  public function get(): ?Entity
  {
    return $this->getResults();
  }
}
