<?php

namespace Drupal\paddix\Adapter;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\paddix\Client\ApiClient;
use Drupal\paddix\Event\EntitySynchronizeEvent;
use Drupal\paddix\Formatter\EntityFormatter;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractMappedAdapter extends AbstractAdapter implements AdapterInterface, MappedAdapterInterface {
  protected $formatter;

  public function __construct(ApiClient $client, EntityFormatter $formatter) {
    parent::__construct($client);

    $this->formatter = $formatter;
  }

  protected abstract function getMappedRoute(ContentEntityBase $entity, array $mapping);

  protected function buildMappedBody(ContentEntityBase $entity, array $mapping) {
    $body = $this->formatter->format($entity, $mapping['fields']);
    if (isset($mapping['defaults'])) {
      $body = array_merge($mapping['defaults'], $body);
    }

    return $body;
  }

  protected function dispatchSynchronizeEvent($statusCode, ContentEntityBase $content, $response) {
    $event = new EntitySynchronizeEvent($this->getType(), $content, $response);

    switch ($statusCode) {
      case Response::HTTP_CREATED:
        $eventType = EntitySynchronizeEvent::ADD;
        break;
      case Response::HTTP_NO_CONTENT:
        $eventType = EntitySynchronizeEvent::DELETE;
        break;
      default:
        $eventType = EntitySynchronizeEvent::UPDATE;
    }

    \Drupal::service('event_dispatcher')->dispatch($eventType, $event);
  }
}
