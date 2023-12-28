<?php

namespace Drupal\kamihaya_cms_deel;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The class interacts with Deel API.
 */
class DeelApi {

  /**
   * API Base URL.
   */
  protected string $baseUri;

  /**
   * Token to access Deel API.
   */
  protected string $token;

  /**
   * GuzzleHttp\ClientInterface definition.
   */
  protected ClientInterface $httpClient;

  /**
   * Configuration Factory.
   */
  protected ConfigFactory $configFactory;

  /**
   * Current request.
   */
  protected Request $request;

  /**
   * {@inheritdoc}
   */
  public function __construct(
      ClientInterface $http_client,
      EntityTypeManagerInterface $entity_type_manager,
      CacheBackendInterface $cache,
      ConfigFactoryInterface $configFactory) {
    $this->httpClient = $http_client;
    $this->configFactory = $configFactory;
    $this->baseUri = $this->configFactory->get('kamihaya_cms_deel.settings')->get('api_url');
  }

  /**
   * Constructs a API request with required options.
   *
   * @var $method
   *   The type of request.
   * @var $endpoint
   *   API endpoint.
   * @var $query
   *   Query parameters sent to the API.
   */
  protected function request($method, $endpoint, array $query = []) {
    $token = $this->configFactory->get('kamihaya_cms_deel.settings')->get('token');

    $response = [];
    if ($method === 'get') {
      $url = Url::fromUri($this->baseUri . $endpoint, [
        // 'query' => $query,
      ]);
      try {
        $response = $this->httpClient->request($method, $url->toString(), [
          'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'accept' => 'application/json',
          ],
          'timeout' => 5,
        ]);

      }
      catch (ClientException $e) {
        $response = $e->getResponse();
      }
      catch (ServerException $e) {
        $response = $e->getResponse();
      }
      return $response;
    }
  }

  /**
   * Parses the API JSON response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   API response object.
   *
   * @return array
   *   Json from API response parsed to array format.
   */
  public function parseReponse(ResponseInterface $response): array {
    $data = $response->getBody()->getContents();
    $data = Json::decode($data);
    return $data;
  }

  /**
   * Get the list of contracts.
   *
   * @return array
   *   An array of contracts.
   */
  public function getContracts(): array {
    $response = $this->request('get', '/rest/v1/contracts');
    return $this->parseReponse($response);
  }

  /**
   * Get the list of peoples.
   *
   * @return array
   *   An array of peoples.
   */
  public function getPeople(): array {
    $response = $this->request('get', '/rest/v1/people');
    return $this->parseReponse($response);
  }

  /**
   * Get the list of managers.
   *
   * @return array
   *   An array of managers.
   */
  public function getManagers(): array {
    $response = $this->request('get', '/rest/v1/managers');
    return $this->parseReponse($response);
  }

}
