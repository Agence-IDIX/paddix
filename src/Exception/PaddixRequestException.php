<?php

namespace Drupal\paddix\Exception;

class PaddixRequestException extends \RuntimeException {
  private $errors;

  public function setErrors($errors) {
    if (!is_array($errors)) {
      return $this;
    }

    $this->errors = $errors;

    return $this;
  }

  public function getErrors() {
    return $this->errors;
  }
}
