<?php

namespace Drupal\paddix\Adapter;

use Drupal\paddix\Client\ApiClient;
use Drupal\paddix\Form\SettingsForm;

abstract class AbstractAdapter implements AdapterInterface {
  protected $settings;

  protected $client;

  public function __construct(ApiClient $client) {
    $this->settings = \Drupal::config(SettingsForm::CONFIG_KEY);
    $this->client = $client;
  }

  protected abstract function getSynchronizeRoute(array $ids);

  protected function getBaseSynchronizeRoute() {
    return '/v2/external/' . $this->settings->get('application');
  }
}
