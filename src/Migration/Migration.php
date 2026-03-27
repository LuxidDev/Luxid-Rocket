<?php

namespace Rocket\Migration;

use Rocket\Connection\Connection;

abstract class Migration
{
  protected Connection $db;

  public function __construct()
  {
    $this->db = Connection::getInstance();
  }

  abstract public function up(): void;
  abstract public function down(): void;

  protected function getConnection(): Connection
  {
    return $this->db;
  }
}
