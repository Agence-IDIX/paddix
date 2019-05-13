<?php

namespace Drupal\paddix\Event;

use Drupal\Core\Entity\ContentEntityBase;
use Symfony\Component\EventDispatcher\Event;

class EntitySynchronizeEvent extends Event {
  const ADD = 'paddix.entity.add',
    UPDATE = 'paddix.entity.update',
    DELETE = 'paddix.entity.delete';

  protected $type;

  protected $entity;

  protected $response;

  public function __construct($type, ContentEntityBase $entity, $response) {
    $this->type = $type;
    $this->entity = $entity;
    $this->response = $response;
  }

  public function getType() {
    return $this->type;
  }

  public function getEntity() {
    return $this->entity;
  }

  public function getResponse() {
    return $this->response;
  }
}
