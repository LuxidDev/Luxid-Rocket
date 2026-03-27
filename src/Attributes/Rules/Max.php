<?php

namespace Rocket\Attributes\Rules;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Max
{
  protected int $max;
  protected string $message = 'This field must not exceed {max} characters.';

  public function __construct(int $max, ?string $message = null)
  {
    $this->max = $max;
    if ($message) {
      $this->message = $message;
    }
  }

  public function validate($value): bool
  {
    if (empty($value)) {
      return true; // Use Required to enforce presence
    }

    if (is_string($value)) {
      return strlen($value) <= $this->max;
    }

    if (is_numeric($value)) {
      return $value <= $this->max;
    }

    if (is_array($value)) {
      return count($value) <= $this->max;
    }

    return false;
  }

  public function getMessage(): string
  {
    return str_replace('{max}', $this->max, $this->message);
  }
}
