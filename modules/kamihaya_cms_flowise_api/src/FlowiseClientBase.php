<?php

declare(strict_types=1);

namespace Drupal\kamihaya_cms_flowise_api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Url;
use Http\Client\Exception\RequestException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

/**
 * Flowise API Client Base.
 */
class FlowiseClientBase {

  use MessengerTrait;

  /**
   * The base URL for the Flowise API.
   *
   * @var string
   */
  protected string $baseUrl;

  /**
   * The API Key for the Flowise API.
   *
   * @var string
   */
  protected string $apiKey;

  /**
   * Constructs an FlowiseClient object.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected LoggerInterface $logger,
    protected ConfigFactoryInterface $configFactory,
  ) {
    if (empty($this->configFactory->get('kamihaya_cms_flowise_api.settings')->get('endpoint'))) {
      $this->messenger()->addError(t('Flowise API endpoint is not configured. Please set it in @link', [
        '@link' => Link::fromTextAndUrl(t('Flowise Settings'), Url::fromRoute('kamihaya_cms_flowise_api.settings'))->toString(),
      ]));
      return;
    }
    if (empty($this->configFactory->get('kamihaya_cms_flowise_api.settings')->get('api_key'))) {
      $this->messenger()->addError(t('Flowise API key is not configured. Please set it in @link', [
        '@link' => Link::fromTextAndUrl(t('Flowise Settings'), Url::fromRoute('kamihaya_cms_flowise_api.settings'))->toString(),
      ]));
      return;
    }
    $this->baseUrl = $this->configFactory->get('kamihaya_cms_flowise_api.settings')->get('endpoint');
    $this->apiKey = $this->configFactory->get('kamihaya_cms_flowise_api.settings')->get('api_key');
  }

  /**
   * Generic request method.
   */
  public function request(string $method, string $endpoint, array $options = []) {
    $options += [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/json',
        'Authorization' => "Bearer {$this->apiKey}",
      ],
    ];
    try {
      $response = $this->httpClient->request(
        $method,
        $this->baseUrl . $endpoint,
        $options,
        // @todo Add timeout.
      );
      return $this->parseResponse($response) ?: [];
    }
    catch (RequestException $e) {
      $this->logger->error('Flowise API Request failed: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Parses the API response.
   */
  public function parseResponse($response) {
    return json_decode($response->getBody()->getContents(), TRUE);
  }

}
