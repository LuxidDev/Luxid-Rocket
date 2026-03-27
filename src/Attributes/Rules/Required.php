<?php

namespace Rocket\Attributes\Rules;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Required
{
  protected string $message = 'This field is required.';

  public function __construct(?string $message = null)
  {
    if ($message) {
      $this->message = $message;
    }
  }

  public function validate($value): bool
  {
    if (is_null($value)) {
      return false;
    }

    if (is_string($value) && trim($value) === '') {
      return false;
    }

    if (is_array($value) && empty($value)) {
      return false;
    }

    return true;
  }

  public function getMessage(): string
  {
    return $this->message;
  }
}
