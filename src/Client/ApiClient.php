<?php

namespace Drupal\paddix\Client;

use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\paddix\Exception\PaddixRequestException;
use Drupal\paddix\Form\SettingsForm;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class ApiClient {
  const MODULE_KEY = 'paddix',
    TOKEN_KEY = 'paddix_token',
    POST_REQUEST_TIMEOUT = 180;

  private $tempStore;

  protected $settings;

  public function __construct(PrivateTempStoreFactory $tempStoreFactory) {
    $this->tempStore = $tempStoreFactory->get($this::MODULE_KEY);
    $this->settings = \Drupal::config(SettingsForm::CONFIG_KEY);
  }

  protected function call($method, $route, $options) {
    $route = $this->prefixRoute($route);

    $client = \Drupal::httpClient();

    try {
      $response = $client->request($method, $route, $options);
    } catch (RequestException $exception) {
      return $this->manageRequestException($exception);
    }

    return $response;
  }

  private function prefixRoute($route) {
    $host = $this->settings->get('host');
    $hostDetails = parse_url($host);

    if (isset($hostDetails['path']) && strpos($route, $hostDetails['path']) === FALSE) {
      $route = $hostDetails['path'] . $route;
    }

    if (strpos($route, $hostDetails['host']) === FALSE) {
      $route = $hostDetails['scheme'] . '://' . $hostDetails['host'] . $route;
    }

    return $route;
  }

  protected function manageRequestException(RequestException $exception) {
    if ($exception->getCode() === 401 && !empty($this->tempStore->get($this::TOKEN_KEY))) {
      try {
        $this->tempStore->set($this::TOKEN_KEY, null);
      } catch (TempStoreException $exception) {
        \Drupal::logger('paddix')->warning($exception->getMessage());
      }

      return $this->refreshRequest($exception->getRequest());
    }

    switch ($exception->getCode()) {
      case 400:
        $paddixException = new PaddixRequestException(t('Invalid Paddix fields mapping'), $exception->getCode());
        $this->insertExceptionDetails($paddixException, $exception->getResponse());
        throw $paddixException;
      case 401:
        throw new PaddixRequestException(t('Invalid Paddix credentials'), $exception->getCode());
      case 404:
        throw new PaddixRequestException(t('Paddix resource not found'), $exception->getCode());
      case 500:
        throw new PaddixRequestException(t('Internal Paddix error'), $exception->getCode());
      default:
        throw new PaddixRequestException(t('Error on Paddix request (code:' . $exception->getCode() . ')'), $exception->getCode());
    }
  }

  private function insertExceptionDetails(PaddixRequestException $exception, ResponseInterface $response)
  {
    $contents = $response->getBody()->getContents();
    if (empty($contents)) {
      return;
    }

    $message = json_decode($contents, TRUE);
    if (!isset($message['errors'])) {
      return;
    }

    $exception->setErrors($message['errors']);
  }

  private function generateToken() {
    return $this->call('POST', $this->settings->get('host') . '/v2/auth/login', [
      'auth' => [
        $this->settings->get('username'),
        $this->settings->get('password')
      ]
    ]);
  }

  protected function getToken() {
    $token = $this->tempStore->get($this::TOKEN_KEY);
    if (empty($token)) {
      $response = $this->generateToken();
      $contents = json_decode($response->getBody()->getContents(), true);

      $token = $contents['token'];

      try {
        $this->tempStore->set($this::TOKEN_KEY, $token);
      } catch (TempStoreException $exception) {
        \Drupal::logger('paddix')->warning($exception->getMessage());
      }
    }

    return $token;
  }

  protected function makeLoginHeaders() {
    return [
      'Authorization' => 'Bearer ' . $this->getToken(),
      'Content-Type' => 'application/json'
    ];
  }

  public function get($route, array $options = []) {
    $options = array_merge([
      'headers' => $this->makeLoginHeaders()
    ], $options);

    return $this->call('GET', $route, $options);
  }

  public function post($route, array $body, array $options = []) {
    $options = array_merge([
      'headers' => $this->makeLoginHeaders(),
      'json' => $body,
      'timeout' => self::POST_REQUEST_TIMEOUT
    ], $options);

    return $this->call('POST', $route, $options);
  }

  public function patch($route, array $body, array $options = []) {
    $options = array_merge([
      'headers' => $this->makeLoginHeaders(),
      'json' => $body
    ], $options);

    return $this->call('PATCH', $route, $options);
  }

  public function delete($route, array $options = []) {
    $options = array_merge([
      'headers' => $this->makeLoginHeaders()
    ], $options);

    return $this->call('DELETE', $route, $options);
  }

  public function multipart($route, $multipart, array $options = []) {
    $options = array_merge([
      'headers' => [
        'Authorization' => 'Bearer ' . $this->getToken()
      ],
      'multipart' => $multipart
    ], $options);

    return $this->call('POST', $route, $options);
  }

  private function refreshRequest(RequestInterface $request) {
    $options = [
      'headers' => $this->makeLoginHeaders(),
    ];

    if (!empty($request->getBody())) {
      $options['json'] = $request->getBody();
    }

    return $this->call($request->getMethod(), $request->getUri(), $options);
  }

  public function formatResponse(ResponseInterface $response) {
    if ($response->getStatusCode() === Response::HTTP_NO_CONTENT) {
      return null;
    }

    return $this->parseResponse($response);
  }

  protected function parseResponse(ResponseInterface $response) {
    return json_decode($response->getBody()->getContents(), true);
  }
}
