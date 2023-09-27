<?php

namespace Drupal\kamihaya_cms_contentserv_api\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * The ContentServ API client.
 */
class ContentServApi {

  use StringTranslationTrait;

  /**
   * The http client..
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

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
   * The contructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The http client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    Messenger $messenger,
    TranslationInterface $string_translation,
    LoggerInterface $logger) {
    // Setup the http client.
    $this->httpClient = $http_client;
    // Setup the configuration factory.
    $this->config = $config_factory->get('kamihaya_cms_contentserv_api.settings');
    // Setup the messenger.
    $this->messenger = $messenger;
    // Setup the $this->t()
    $this->stringTranslation = $string_translation;
    // Setup the logger.
    $this->logger = $logger;
  }

  /**
   * Get changed data from ContentServ.
   *
   * @param int $start_date
   *   Search from time.
   * @param int $limit
   *   Data count limit.
   * @param int $page
   *   The page of result data.
   *
   * @return array
   *   The changed data array.
   */
  public function getChangedData(int $start_date, int $limit, int $page = 0) {
    if ((!empty($start_date) && $start_date > time()) || $limit < 1) {
      return [];
    }

    $api_url = $this->config->get('contentserv_api_url');
    $folder_id = $this->config->get('contentserv_api_folder_id');
    $username = $this->config->get('contentserv_username');
    $password = $this->config->get('contentserv_password');

    // HTTP header.
    $api_headers = [
      'Content-Type' => 'application/json',
      'Authorization' => 'Basic ' . base64_encode("{$username}:{$password}"),
    ];

    // Request parameter.
    $parameters = [];
    if (!empty($start_date)) {
      // Search from date.
      $parameters['begin'] = date('Y-m-d H:i:s', $start_date);
    }
    // Limit.
    $parameters['limit'] = $limit;
    // Page.
    $parameters['page'] = $page;

    $url = substr($api_url, -1) === '/' ? "{$api_url}{$folder_id}" : "{$api_url}/{$folder_id}";
    try {
      $response = $this->httpClient->get($url, [
        'headers' => $api_headers,
        'query' => $parameters,
      ]);

      $body = json_decode($response->getBody(), TRUE);

      // Contentserv error.
      if ($body['Meta']['Code'] != 200) {
        return $body['Meta'];
      }

      // No data.
      if (empty($body['Meta']['Total']) || empty($body['Products'])) {
        return [];
      }
      return [
        'Products' => $body['Products'],
        'Total' => $body['Meta']['Total'],
      ];
    }
    catch (GuzzleException $e) {
      $this->logger->error('Failed to get the changed data from ContentServ. Error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

}
