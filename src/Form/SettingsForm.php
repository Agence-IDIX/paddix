<?php

namespace Drupal\paddix\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {
  const CONFIG_KEY = 'paddix.settings';

  public function getFormId() {
    return 'paddix_admin_settings';
  }

  protected function getEditableConfigNames() {
    return [
      $this::CONFIG_KEY
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config($this::CONFIG_KEY);

    $form['host'] = array(
      '#type' => 'url',
      '#title' => $this->t('Host'),
      '#default_value' => $config->get('host'),
      '#required' => true
    );

    $form['username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config->get('username'),
      '#required' => true
    );

    $form['password'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $config->get('password'),
      '#required' => true
    );

    $form['application'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Application'),
      '#default_value' => $config->get('application'),
      '#required' => true
    );

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable($this::CONFIG_KEY)
      ->set('host', $form_state->getValue('host'))
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->set('application', $form_state->getValue('application'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
