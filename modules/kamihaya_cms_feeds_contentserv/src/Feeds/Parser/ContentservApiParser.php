<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Parser;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\FetchException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Feeds\Parser\ParserBase;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\StateInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a CSV feed parser.
 *
 * @FeedsParser(
 *   id = "kamihaya_contentserv",
 *   title = "Kamihaya Contentserv",
 *   description = @Translation("Parse Contentserv JSON gotten by Kamihaya Contentserv API."),
 *   form = {
 *     "configuration" = "Drupal\kamihaya_cms_feeds_contentserv\Feeds\Parser\Form\ContentservApiFetcherForm",
 *     "feed" = "Drupal\feeds\kamihaya_cms_feeds_contentserv\Parser\Form\ContentservApiFetcherFeedForm",
 *   },
 * )
 */
class ContentservApiParser extends ParserBase implements ContainerFactoryPluginInterface{

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
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    // Get sources.
    $sources = [];
    $skip_sources = [];
    foreach ($feed->getType()->getMappingSources() as $key => $info) {
      if (isset($info['type']) && $info['type'] !== 'kamihaya_json') {
        $skip_sources[$key] = $key;
        continue;
      }
      if (!empty($info['value']) && trim(strval($info['value'])) !== '') {
        $sources[$key] = $info['value'];
      }
    }

    $fetcher_config = $feed->getType()->getFetcher()->getConfiguration();

    /** @var \Drupal\kamihaya_cms_feeds_contentserv\Feeds\Result\ContentservApiFetcherResultInterface $fetcher_result */
    if (empty($fetcher_result->getProducts())) {
      throw new EmptyFeedException();
    }
    $result = new ParserResult();

    try {
      $url = $fetcher_config['json_api_url'];
      $data_url = "$url/core/v1/product/";
      $data_type = $fetcher_config['data_type'];
      $options = [
        RequestOptions::TIMEOUT => $fetcher_config['request_timeout'],
        RequestOptions::HEADERS => [
          'Content-Type' => 'application/json',
          'Authorization' => "Bearer {$fetcher_result->getAccessToken()}",
        ],
      ];

      if (empty($state->pointer)) {
        $state->pointer = 0;
      }
      $products = array_slice($fetcher_result->getProducts(), $state->pointer, $this->configuration['data_limit']);
      foreach ($products as $product) {
        // Get the detail of product.
        $response = $this->httpClient->get("$data_url{$product['ID']}", $options);
        $data = json_decode($response->getBody()->getContents(), TRUE);
        if (empty($data[$data_type])) {
          continue;
        }
        $item = new DynamicItem();
        foreach ($sources as $key => $json_key) {
          if (isset($skip_sources[$key])) {
            // Skip custom sources that are not of type "csv".
            continue;
          }

          $value = $this->getAttributeValue($data[$data_type], $json_key);
          $item->set($key, $value);
        }

        $result->addItem($item);
        $state->pointer++;
      }

      // Report progress.
      $state->total = count($fetcher_result->getProducts());
      $state->progress($state->total, $state->pointer);

    } catch (RequestException $e) {
      $state->setCompleted();
      $args = ['%error' => $e->getMessage()];
      throw new FetchException(strtr('The error occurs while getting data because of error "%error".', $args));
    }

    // Set progress to complete if no more results are parsed. Can happen with
    // empty lines in CSV.
    if (!$result->count()) {
      $state->setCompleted();
    }

    return $result;
  }

  /**
   * Get the attribute value from JSON.
   *
   * @param array $json
   *   The JSON data.
   * @param string $render_value
   *   The render value.
   *
   * @return string
   *   The attribute value.
   */
  private function getAttributeValue(array $json, $render_value) {
    $render_value = str_replace(' ', '', $render_value);
    $attributes = explode(':', $render_value);
    $value = $json;
    foreach ($attributes as $attribute) {
      if (!is_array($value)) {
        return '';
      }
      $attribute = trim($attribute);
      if (empty($value[$attribute])) {
        '';
      }
      if (!empty($value[$attribute])) {
        $value = $value[$attribute];
        continue;
      }
      if (strpos($attribute, '|') !== FALSE) {
        $attrs = explode('|', $attribute);
        if (!empty($attrs[0]) && strpos($attrs[0], '=') !== FALSE) {
          $key = explode('=', $attrs[0])[0];
          $val = explode('=', $attrs[0])[1];
          $val_key = $attrs[1];
          foreach ($value as $item) {
            if (!empty($item[$key]) && $item[$key] === $val && !empty($item[$val_key])) {
              return $item[$val_key];
            }
          }
          return '';
        }
      }
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedCustomSourcePlugins(): array {
    return ['kamihaya_json'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'data_limit' => 100,
    ];
  }

}
