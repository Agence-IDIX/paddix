<?php

namespace Drupal\paddix\Adapter;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\paddix\Client\ApiClient;
use Drupal\paddix\Client\FlatplanClient;
use Drupal\paddix\Formatter\EntityFormatter;

class FlatplanAdapter extends AbstractMappedAdapter {
  public function getType() {
    return 'flatplan';
  }

  protected function getSynchronizeRoute(array $ids) {
    return $this->getBaseSynchronizeRoute() . '/editions/' . $ids['edition'] . '/flatplans/' . $ids['flatplan'];
  }

  protected function getMappedRoute(ContentEntityBase $entity, array $mapping) {
    $edition = $entity->get($mapping['edition'])->getString();
    $flatplan = $entity->get($mapping['flatplan'])->getString();

    return $this->getSynchronizeRoute([
      'edition' => $edition,
      'flatplan' => $flatplan
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

  public function getMappedEntity(ContentEntityBase $entity, array $mapping, array $parameters = []) {
    $route = $this->getMappedRoute($entity, $mapping);
    $response = $this->client->get($route, [
      'query' => $parameters
    ]);

    return $this->client->formatResponse($response);
  }

  public function deleteMappedEntity(ContentEntityBase $entity, array $mapping) {
    $route = $this->getMappedRoute($entity, $mapping);

    $response = $this->client->delete($route);
    $contents = $this->client->formatResponse($response);

    $this->dispatchSynchronizeEvent($response->getStatusCode(), $entity, $contents);
  }
}
