<?php

namespace Rocket\Relations;

use Rocket\ORM\Entity;

class HasOne extends Relation
{
  public function __construct(Entity $parent, string $relatedClass, string $foreignKey, string $localKey)
  {
    parent::__construct($parent, $relatedClass, $foreignKey, $localKey);
    $this->addConstraints();
  }

  public function addConstraints(): void
  {
    // No constraints needed for lazy loading
  }

  public function getResults(): ?Entity
  {
    echo "    HasOne query: {$this->foreignKey} = {$this->parent->{$this->localKey}}\n";
    $query = $this->getRelatedQuery();
    $query->where($this->foreignKey, '=', $this->parent->{$this->localKey});

    $result = $query->first();
    echo "    HasOne found: " . (is_null($result) ? 'NULL' : get_class($result)) . "\n";
    return $result;
  }

  public function get(): ?Entity
  {
    return $this->getResults();
  }
}
