<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Fetcher;

use Drupal\feeds\Exception\FetchException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\StateInterface;
use Drupal\kamihaya_cms_feeds_contentserv\Result\ContentservApiFetcherResult;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

/**
 * Defines an Contentserv API Data fetcher.
 *
 * @FeedsFetcher(
 *   id = "kamihaya_contentserv_api_data",
 *   title = @Translation("Kamihaya Contentserv API Data"),
 *   description = @Translation("Get one data via Contentserv API."),
 *   form = {
 *     "configuration" = "Drupal\kamihaya_cms_feeds_contentserv\Feeds\Fetcher\Form\ContentservApiDataFetcherForm",
 *     "feed" = "Drupal\kamihaya_cms_feeds_contentserv\Feeds\Fetcher\Form\ContentservApiDataFetcherFeedForm",
 *   }
 * )
 */
class ContentservApiDataFetcher extends ContentservApiFetcher {

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {
    $data_type = $this->configuration['data_type'];
    $source = $feed->getSource();
    $json = [];
    // Get the JSON data from the feed.
    if ($feed->hasField('field_json_data') && !empty($feed->get('field_json_data')->getValue())) {
      $json_data = $feed->get('field_json_data')->getValue()[0]['value'];
      $json = json_decode($json_data, TRUE);
    }
    $url = $this->configuration['json_api_url'];
    $data_url = "/core/v1/" . strtolower($data_type) . '/';
    $feed_config = $feed->getConfigurationFor($feed->getType()->getFetcher());
    $token = '';
    // Get the access token from the feed configuration.
    if (!empty($feed_config['access_token'])) {
      $token = $feed_config['access_token'];
    }

    try {
      // Get the access token if it is not set.
      if (empty($token)) {
        $token = $this->getAccessToken($feed, $url);
        $feed_config['access_token'] = $token;
        $feed->setConfigurationFor($feed->getType()->getFetcher(), $feed_config);
      }

      if (!empty($json)) {
        // Return the JSON data if it is set.
        return new ContentservApiFetcherResult([[$data_type => $json]], $token);
      }
      $options = [];
      if ($this->hasTagsInSource($feed)) {
        // Set the expand query if the feed has 'Tags' in source.
        $options[RequestOptions::QUERY] = ['expand' => 'Tags'];
      }
      // Get the detail of product.
      $response = $this->getData($feed, $url, "$data_url{$source}" , $token, $options);
      $result = json_decode($response, TRUE);
      if (empty($result[$data_type])) {
        throw new FetchException($this->t('@name: Faild to fetch the data. [@type ID: @id]', [
          '@name' => $feed->label(),
          '@type' => $data_type,
          '@id' => $source,
        ]));
      }
    }
    catch (GuzzleException $e) {
      $args = [
        '@name' => $feed->label(),
        '%error' => $e->getMessage(),
        '@type' => $data_type,
        '@id' => $source,
      ];
      throw new FetchException(strtr('@name: The error occurs while fetching data because of error "%error". [@type ID: @id]', $args));
    }

    return new ContentservApiFetcherResult([$result], $token);
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
      'request_timeout' => 30,
    ];
  }

}
