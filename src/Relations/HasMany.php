<?php

namespace Rocket\Relations;

use Rocket\ORM\Entity;

class HasMany extends Relation
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

  public function getResults(): array
  {
    echo "    HasMany query: {$this->foreignKey} = {$this->parent->{$this->localKey}}\n";
    $query = $this->getRelatedQuery();
    $query->where($this->foreignKey, '=', $this->parent->{$this->localKey});

    $result = $query->all();
    echo "    HasMany found: " . count($result) . " items\n";
    return $result;
  }

  public function get(): array
  {
    return $this->getResults();
  }

  public function create(array $attributes): Entity
  {
    $attributes[$this->foreignKey] = $this->parent->{$this->localKey};
    $related = new $this->relatedClass();
    $related->load($attributes);
    $related->save();

    return $related;
  }
}
