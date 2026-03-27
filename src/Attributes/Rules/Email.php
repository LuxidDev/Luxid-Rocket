<?php

namespace Rocket\Attributes\Rules;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Email
{
  protected string $message = 'This field must be a valid email address.';

  public function __construct(?string $message = null)
  {
    if ($message) {
      $this->message = $message;
    }
  }

  public function validate($value): bool
  {
    if (empty($value)) {
      return true; // Use Required to enforce presence
    }

    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
  }

  public function getMessage(): string
  {
    return $this->message;
  }
}
