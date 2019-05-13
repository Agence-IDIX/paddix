<?php

namespace Drupal\paddix\Formatter;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\paddix\Adapter\AttachmentAdapter;

class EntityFormatter {
  const SINGLE_CARDINALITY = 1;

  private $attachmentAdapter;

  public function __construct(AttachmentAdapter $attachmentAdapter) {
    $this->attachmentAdapter = $attachmentAdapter;
  }

  public function format(ContentEntityBase $entity, array $fields) {
    $body = $this->formatFields($entity, $fields);

    return $body;
  }

  private function formatFields(ContentEntityBase $entity, $fields) {
    $body = [];

    foreach ($fields as $key => $field) {
      if (is_array($field)) {
        $body[$key] = $this->formatFields($entity, $field);
      } else {
        $body[$key] = $this->formatField($entity, $field);
      }
    }

    return $body;
  }

  private function formatField(ContentEntityBase $entity, $key) {
    $field = $entity->get($key);
    $values = $this->extractFieldValues($field);

    $cardinality = $this->getCardinality($field);
    if ($cardinality === $this::SINGLE_CARDINALITY) {
      if (!isset($values[0])) {
        return null;
      }

      return $values[0];
    }

    return $values;
  }

  private function extractFieldValues(FieldItemListInterface $field) {
    if ($field instanceof FileFieldItemList) {
      return $this->extractFileFieldValues($field);
    }

    $values = array_map(function ($entry) {
      return $entry['value'];
    }, $field->getValue());

    return $values;
  }

  private function extractFileFieldValues(FileFieldItemList $field) {
    $values = [];

    $entities = $field->referencedEntities();
    foreach ($entities as $entity) {
      $values[] = $this->formatFileFieldValue($entity);
    }

    return $values;
  }

  private function formatFileFieldValue(File $file) {
    $response = $this->attachmentAdapter->synchronizeFile($file);

    return $response['id'];
  }

  private function getCardinality(FieldItemListInterface $field) {
    $definition = $field->getFieldDefinition()->getFieldStorageDefinition();
    if (!$definition instanceof FieldStorageConfig) {
      return $this::SINGLE_CARDINALITY;
    }

    return $definition->get('cardinality');
  }
}
