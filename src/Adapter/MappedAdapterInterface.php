<?php

namespace Drupal\paddix\Adapter;

use Drupal\Core\Entity\ContentEntityBase;

interface MappedAdapterInterface {
  public function synchronizeMappedEntity(ContentEntityBase $entity, array $mapping);

  public function deleteMappedEntity(ContentEntityBase $entity, array $mapping);
}
