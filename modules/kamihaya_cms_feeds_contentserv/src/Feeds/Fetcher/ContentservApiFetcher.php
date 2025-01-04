<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Fetcher;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\FetchException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Annotation\FeedsFetcher;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\StateInterface;
use Drupal\kamihaya_cms_feeds_contentserv\Result\ContentservApiFetcherResult;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an Contentserv API fetcher.
 *
 * @FeedsFetcher(
 *   id = "kamihaya_contentserv_api",
 *   title = @Translation("Kamihaya Contentserv API"),
 *   description = @Translation("Get data via Contentserv API."),
 *   form = {
 *     "configuration" = "Drupal\kamihaya_cms_feeds_contentserv\Feeds\Fetcher\Form\ContentservApiFetcherForm",
 *     "feed" = "Drupal\kamihaya_cms_feeds_contentserv\Feeds\Fetcher\Form\ContentservApiFetcherFeedForm",
 *   }
 * )
 */
class ContentservApiFetcher extends PluginBase implements FetcherInterface, ContainerFactoryPluginInterface {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The Guzzle client.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, protected ClientInterface $httpClient) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {
    $url = $this->configuration['json_api_url'];
    $auth_url = "$url/auth/v1/token";
    $list_url = "$url/core/v1/product/changes/";
    $data_type = $this->configuration['data_type'];

    $products = [];
    $token = '';

    $options = [
      RequestOptions::TIMEOUT => $this->configuration['request_timeout'],
      RequestOptions::HEADERS => [
       'Content-Type' => 'application/json',
        'Authorization' => "CSAuth {$this->configuration['api_key']}:{$this->configuration['secret']}",
      ],
    ];

    try {
      // Get the access token.
      $response = $this->httpClient->get($auth_url, $options);
      $result = json_decode($response->getBody()->getContents(), TRUE);
      $token = !empty($result['access_token']) ? $result['access_token'] : '';
      if (empty($token)) {
        throw new FetchException($this->t('Faild to get the access token'));
      }

      // Get the last imported time to fetch only the changed data.
      $last_imported_time = 0;
      if (!empty($feed->getImportedTime())) {
        $last_imported_time = $feed->getImportedTime();
      }
      $options = [
        RequestOptions::TIMEOUT => $this->configuration['request_timeout'],
        RequestOptions::HEADERS => [
          'Content-Type' => 'application/json',
          'Authorization' => "Bearer $token",
        ],
        RequestOptions::QUERY => [
          'filter' => 'IsFolder=0',
          'begin' => date('Y-m-d H:i:s', $last_imported_time),
          'limit' => $this->configuration['limit']
        ],
      ];

      // Get the list of products.
      $response = $this->httpClient->get("$list_url{$this->configuration['folder_id']}", $options);
      $result = json_decode($response->getBody()->getContents(), TRUE);
      if (empty($result['Meta']['Total'])) {
        $state->setMessage($this->t('No data found.'));
        throw new EmptyFeedException();
      }
      $products = $result["{$data_type}s"] ? $result["{$data_type}s"] : [];

      // Get the rest of the data if the total count is more than the limit.
      if ($result['Meta']['Total'] > $this->configuration['limit']) {
        $count = ceil($result['Meta']['Total'] / $this->configuration['limit']) - 1;
        for ($i = 1; $i <= $count; $i++) {
          $options[RequestOptions::QUERY]['page'] = $i;
          $response = $this->httpClient->get("$list_url{$this->configuration['folder_id']}", $options);
          $result = json_decode($response->getBody()->getContents(), TRUE);
          if (empty($result["{$data_type}s"])) {
            $state->setMessage($this->t('No additional data found.'));
            throw new EmptyFeedException();
          }
          $products = array_merge($products, $result["{$data_type}s"]);
        }
      }
    }
    catch (RequestException $e) {
      $args = ['%error' => $e->getMessage()];
      throw new FetchException(strtr('The error occurs while getting data because of error "%error".', $args));
    }

    return new ContentservApiFetcherResult($products, $token);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'json_api_url' => '',
      'api_key' => '',
      'secret' => '',
      'folder_id' => '',
      'data_type' => 'Product',
      'limit' => '1000',
      'request_timeout' => 30,
    ];
  }

}
