<?php

declare(strict_types=1);

namespace Drupal\kamihaya_cms_exabase_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Http\Client\Exception\RequestException;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Exabase API Client.
 */
final class ExabaseClient {

  protected ClientInterface $httpClient;
  protected LoggerInterface $logger;
  protected ConfigFactoryInterface $configFactory;
  protected string $baseUrl;

  /**
   * Constructs an ExabaseClient object.
   */
  public function __construct(
    ClientInterface $http_client,
    LoggerInterface $logger,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->httpClient = $http_client;
    $this->logger = $logger;
    $this->configFactory = $config_factory;
    $this->baseUrl = $this->configFactory->get('kamihaya_cms_exabase_api.settings')->get('endpoint');
  }

  /**
   * Generic request method.
   */
  public function request(string $method, string $endpoint, array $options = []) {
    $options += ['timeout' => 30];
    try {
      $response = $this->httpClient->request(
            $method,
            $this->baseUrl . $endpoint,
            $options,
            // @todo Add timeout.
        );
      return $this->parseResponse($response);
    }
    catch (RequestException $e) {
      $this->logger->error('Exabase API Request failed: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Parses the API response.
   */
  public function parseResponse($response) {
    return json_decode($response->getBody()->getContents(), TRUE);
  }

  /**
   * Checks API health.
   */
  public function getHealth() {
    return $this->request('GET', '/health');
  }

  /**
   * Calls the extract endpoint.
   */
  public function postExtract(string $file_path) {
    $file_stream = fopen($file_path, 'r');

    return $this->request(
          'POST', '/extract', [
            'multipart' => [
          [
            'name' => 'file',
            'contents' => $file_stream,
            'filename' => basename($file_path),
            'headers' => [
            // @todo Do we need this dynamically added based on file type.
              'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
          ],
            ],
          ]
      );
  }

  /**
   * Calls the check endpoint.
   */
  public function postCheck(string $summary) {
    return $this->request(
          'POST', '/check', [
            'headers' => [
              'Accept' => 'application/json',
              'Content-Type' => 'application/json',
            ],
            'json' => ['spec_summary' => $summary],
          ],
      );
  }

  /**
   * Calls the check endpoint.
   */
  public function postReCheck(string $summary, string $check_result) {
    return $this->request(
          'POST', '/recheck', [
            'headers' => [
              'Accept' => 'application/json',
              'Content-Type' => 'application/json',
            ],
            'json' => [
              'spec_summary' => $summary,
              'checkresult' => $check_result,
            ],
          ],
      );
  }

}
