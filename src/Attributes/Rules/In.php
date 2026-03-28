<?php

namespace Rocket\Attributes\Rules;

use Attribute;

/**
 * In Validation Rule
 * 
 * Validates that a value is in a given list of allowed values.
 * 
 * @package Rocket\Attributes\Rules
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class In
{
  protected array $allowed;
  protected string $message = 'The selected :field is invalid.';

  public function __construct(array $allowed, ?string $message = null)
  {
    $this->allowed = $allowed;
    if ($message) {
      $this->message = $message;
    }
  }

  public function validate($value): bool
  {
    if (empty($value)) {
      return true; // Use Required to enforce presence
    }

    return in_array($value, $this->allowed);
  }

  public function getMessage(): string
  {
    return $this->message;
  }

  public function getAllowed(): array
  {
    return $this->allowed;
  }
}
