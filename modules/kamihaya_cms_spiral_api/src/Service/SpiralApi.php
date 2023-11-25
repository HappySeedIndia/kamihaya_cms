<?php

namespace Drupal\kamihaya_cms_spiral_api\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * The SPIRAL API client.
 */
class SpiralApi {

  use StringTranslationTrait;

  /**
   * The http client..
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The api url.
   *
   * @var string
   */
  protected $apiUrl;

  /**
   * The api key.
   *
   * @var string
   */
  protected $apiKey;
  /**
   * The application ID.
   *
   * @var string
   */
  protected $appId;

  /**
   * The api DB.
   *
   * @var string
   */
  protected $apiDb;

  /**
   * The site ID.
   *
   * @var string
   */
  protected $siteId;

  /**
   * The authentication ID.
   *
   * @var string
   */
  protected $authId;

  /**
   * The contructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The http client.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The user account.
   */
  public function __construct(
    LoggerInterface $logger,
    ConfigFactoryInterface $config_factory,
    ClientInterface $http_client,
    Messenger $messenger,
    TranslationInterface $string_translation,
    AccountInterface $current_user) {
    $this->logger = $logger;
    $this->config = $config_factory->get('kamihaya_cms_spiral_api.settings');
    $this->httpClient = $http_client;
    $this->messenger = $messenger;
    $this->stringTranslation = $string_translation;
    $this->currentUser = $current_user;
    $this->apiUrl = $this->config->get('spiral_api_url');
    $this->apiKey = $this->config->get('spiral_api_key');
    $this->appId = $this->config->get('spiral_application_id');
    $this->apiDb = $this->config->get('spiral_db_id');
    $this->siteId = $this->config->get('spiral_site_id');
    $this->authId = $this->config->get('spiral_authentication_id');
  }

  /**
   * Login.
   *
   * @param string $email
   *   The mail address.
   * @param array $password
   *   The password.
   */
  public function login($email, $password) {
    if (empty($email) || empty($password)) {
      return ["code" => "000001", "message" => "function error no data"];
    }

    // HTTP header.
    $api_headers = [
      'Content-Type' => 'application/json',
      'Authorization' => "Bearer {$this->apiKey}",
    ];

    // Set up the parameter.
    $parameters = [];

    $parameters["id"] = $email;
    $parameters["password"] = $password;

    $json_body = json_encode($parameters);

    $url = "{$this->apiUrl}sites/{$this->siteId}/authentications/{$this->authId}/login";

    try {
      $response = $this->httpClient->request('POST', $url, [
        'headers' => $api_headers,
        'body' => $json_body,
      ]);
      $data = json_decode($response->getBody(), TRUE);

      // No data.
      if (empty($data["recordId"])) {
        return NULL;
      }
      $record_id = $data["recordId"];

      return $this->readByRedordId($record_id);
    }
    catch (GuzzleException $e) {
      $this->logger->error('Failed to login with SPIRAL. Error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Get one data from record ID.
   *
   * @param string $recordId
   *   The record id.
   */
  public function readByRedordId($recordId) {
    if (empty($recordId)) {
      return ["code" => "000001", "message" => "function error no data"];
    }

    // HTTP header.
    $api_headers = [
      'Content-Type' => 'application/json',
      'Authorization' => "Bearer {$this->apiKey}",
    ];

    // Send with curl.
    $url = "{$this->apiUrl}apps/{$this->appId}/dbs/{$this->apiDb}/records/{$recordId}";

    try {
      $response = $this->httpClient->request('GET', $url, [
        'headers' => $api_headers,
      ]);
      $data = json_decode($response->getBody(), TRUE);

      // No item.
      if (empty($data["item"])) {
        return NULL;
      }

      foreach ($data['item'] as $key => $value) {
        if (strpos($key, '_') !== 0 && $key !== 'password') {
          continue;
        }
        unset($data['item'][$key]);
      }

      return $data["item"];
    }
    catch (GuzzleException $e) {
      $this->logger->error('Failed to get data from SPIRAL. Error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Change the database.
   *
   * @param string $db
   *   The db name.
   */
  public function apiDbChange($db) {
    $this->apiDb = $db;
  }

  /**
   * Set the default database.
   */
  public function apiDbReset() {
    $this->apiDb = $this->config->get('spiral_db_id');
  }

}
