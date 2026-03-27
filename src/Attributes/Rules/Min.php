<?php

namespace Rocket\Attributes\Rules;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Min
{
  protected int $min;
  protected string $message = 'This field must be at least {min} characters.';

  public function __construct(int $min, ?string $message = null)
  {
    $this->min = $min;
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
      return strlen($value) >= $this->min;
    }

    if (is_numeric($value)) {
      return $value >= $this->min;
    }

    if (is_array($value)) {
      return count($value) >= $this->min;
    }

    return false;
  }

  public function getMessage(): string
  {
    return str_replace('{min}', $this->min, $this->message);
  }
}
