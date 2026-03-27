<?php

namespace Rocket\Relations;

use Rocket\ORM\Entity;
use Rocket\Query\QueryBuilder;

abstract class Relation
{
  protected Entity $parent;
  protected string $relatedClass;
  protected string $foreignKey;
  protected string $localKey;

  public function __construct(Entity $parent, string $relatedClass, string $foreignKey, string $localKey)
  {
    $this->parent = $parent;
    $this->relatedClass = $relatedClass;
    $this->foreignKey = $foreignKey;
    $this->localKey = $localKey;
  }

  abstract public function getResults();
  abstract public function addConstraints();

  protected function getRelatedQuery(): QueryBuilder
  {
    $query = $this->relatedClass::query();
    return $query;
  }
}
