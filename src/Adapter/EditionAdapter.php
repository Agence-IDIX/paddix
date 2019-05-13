<?php

namespace Drupal\paddix\Adapter;

use Drupal\Core\Entity\ContentEntityBase;

class EditionAdapter extends AbstractMappedAdapter {
  public function getType() {
    return 'edition';
  }

  protected function getSynchronizeRoute(array $ids) {
    return $this->getBaseSynchronizeRoute() . '/editions/' . $ids['edition'];
  }

  protected function getMappedRoute(ContentEntityBase $entity, array $mapping) {
    $id = $entity->get($mapping['edition'])->value;

    return $this->getSynchronizeRoute([
      'edition' => $id
    ]);
  }

  public function synchronizeMappedEntity(ContentEntityBase $entity, array $mapping) {
    $route = $this->getMappedRoute($entity, $mapping);
    $body = $this->buildMappedBody($entity, $mapping);

    $response = $this->client->post($route, $body);
    $contents = $this->client->formatResponse($response);

    $this->dispatchSynchronizeEvent($response->getStatusCode(), $entity, $contents);

    return $contents;
  }

  public function deleteMappedEntity(ContentEntityBase $entity, array $mapping) {
    $route = $this->getMappedRoute($entity, $mapping);

    $response = $this->client->delete($route);
    $contents = $this->client->formatResponse($response);

    $this->dispatchSynchronizeEvent($response->getStatusCode(), $entity, $contents);
  }
}
