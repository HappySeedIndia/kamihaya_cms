<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Fetcher;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\FetchException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\StateInterface;
use Drupal\kamihaya_cms_feeds_contentserv\Result\ContentservApiFetcherResult;
use Drupal\kamihaya_cms_feeds_contentserv\Trait\ContentservApiTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
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

  use ContentservApiTrait;

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
    $data_type = $this->configuration['data_type'];
    $list_url = "/core/v1/" . strtolower($data_type) . '/changes/';
    $results = [];

    try {
      // Get the access token.
      $token = $this->getAccessToken($feed, $url);

      // Get the last imported time to fetch only the changed data.
      $last_imported_time = 0;
      if (!empty($feed->getImportedTime())) {
        $last_imported_time = $feed->getImportedTime();
      }
      $options = [
        RequestOptions::QUERY => [
          'filter' => 'IsFolder=0',
          'begin' => date('Y-m-d H:i:s', $last_imported_time),
          'limit' => $this->configuration['limit'],
        ],
      ];

      // Get the list of results.
      $response = $this->getData($feed, $url, "$list_url{$this->configuration['folder_id']}", $token, $options);
      $result = json_decode($response, TRUE);
      if (empty($result['Meta']['Total'])) {
        // Throw an exception if Total value is empty.
        $state->setMessage($this->t('No data found while fetching data.'));
        throw new EmptyFeedException(strtr('@name: No data found while fetching data.', ['@name' => $feed->label()]));
      }
      $results = $result["{$data_type}s"] ? $result["{$data_type}s"] : [];

      // Get the rest of the data if the total count is more than the limit.
      if ($result['Meta']['Total'] > $this->configuration['limit']) {
        $count = ceil($result['Meta']['Total'] / $this->configuration['limit']) - 1;
        for ($i = 1; $i <= $count; $i++) {
          // Set the page number to get the additional data.
          $options[RequestOptions::QUERY]['page'] = $i;
          $response = $this->getData($feed, $url, "$list_url{$this->configuration['folder_id']}", $token, $options);
          $result = json_decode($response, TRUE);
          if (empty($result["{$data_type}s"])) {
            // Throw an exception if no additional data found.
            $state->setMessage($this->t('No additional data found while fetching data.'));
            throw new EmptyFeedException(strtr('@name: No additional data found while fetching data.', ['@name' => $feed->label()]));
          }
          $results = array_merge($results, $result["{$data_type}s"]);
        }
      }
    }
    catch (GuzzleException $e) {
      $args = [
        '@name' => $feed->label(),
        '%error' => $e->getMessage(),
      ];
      throw new FetchException(strtr('@name:  The error occurs while fetched data because of error "%error".', $args));
    }

    return new ContentservApiFetcherResult($results, $token);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultFeedConfiguration() {
    return ['access_token' => ''];
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
      'scheduled_execution' => FALSE,
      'scheduled_minute' => 0,
    ];
  }

}
