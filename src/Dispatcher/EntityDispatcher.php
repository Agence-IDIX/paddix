<?php

namespace Drupal\paddix\Dispatcher;

use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\node\Entity\Node;
use Drupal\paddix\Adapter\AbstractMappedAdapter;
use Drupal\paddix\Exception\PaddixRequestException;
use Drupal\paddix\Form\SettingsForm;

class EntityDispatcher {
  public function synchronize(ContentEntityBase $entity) {
    $mapping = $this->getMapping($entity);
    if (empty($mapping)) {
      return;
    }

    foreach ($mapping['adapters'] as $adapter) {
      try {
        $this->insertUpdateEntity($entity, $adapter);
      } catch (PaddixRequestException $exception) {
        drupal_set_message(t($exception->getMessage()) . ' (code: ' . $exception->getCode() . ')', 'error', true);
        return;
      }
    }
  }

  public function deleteSynchronized(ContentEntityBase $entity) {
    $mapping = $this->getMapping($entity);
    if (empty($mapping)) {
      return;
    }

    $adapters = array_reverse($mapping['adapters']);

    foreach ($adapters as $adapter) {
      try {
        $this->deleteEntity($entity, $adapter);
      } catch (PaddixRequestException $exception) {
        drupal_set_message(t($exception->getMessage()) . ' (code: ' . $exception->getCode() . ')', 'error', true);
        return;
      }
    }
  }

  public function getMapping(ContentEntityBase $entity) {
    $settings = \Drupal::config(SettingsForm::CONFIG_KEY);
    foreach ($settings->get('mapping') as $mapping) {
      if ($this->mappingMatchEntity($mapping, $entity)) {
        return $mapping;
      }
    }

    return null;
  }

  public function getMappingAdapter(ContentEntityBase $entity, $service) {
    $mapping = $this->getMapping($entity);
    if (empty($mapping)) {
      return null;
    }

    foreach ($mapping['adapters'] as $adapter) {
      if ($adapter['service'] === $service) {
        return $adapter;
      }
    }

    return null;
  }

  private function mappingMatchEntity(array $mapping, ContentEntityBase $entity) {
    if (!$entity instanceof $mapping['class']) {
      return false;
    }

    if ($entity instanceof Node && $entity->getType() !== $mapping['type']) {
      return false;
    }

    if ($entity instanceof Product && $entity->get('type')->getString() !== $mapping['type']) {
      return false;
    }

    return true;
  }

  private function insertUpdateEntity($entity, $mapping) {
    $adapter = \Drupal::service($mapping['service']);
    if (!$adapter instanceof AbstractMappedAdapter) {
      return null;
    }

    return $adapter->synchronizeMappedEntity($entity, $mapping);
  }

  private function deleteEntity($entity, $mapping) {
    $adapter = \Drupal::service($mapping['service']);
    if (!$adapter instanceof AbstractMappedAdapter) {
      return null;
    }

    return $adapter->deleteMappedEntity($entity, $mapping);
  }
}
