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
class ExabaseClientBase {

  /**
   * The base URL for the Exabase API.
   *
   * @var string
   */
  protected string $baseUrl;

  /**
   * Constructs an ExabaseClient object.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected LoggerInterface $logger,
    protected ConfigFactoryInterface $configFactory,
  ) {
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
}
