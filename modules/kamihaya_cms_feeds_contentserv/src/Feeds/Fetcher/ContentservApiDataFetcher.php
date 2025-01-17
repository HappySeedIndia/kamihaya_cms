<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Fetcher;

use Drupal\feeds\Exception\FetchException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\StateInterface;
use Drupal\kamihaya_cms_feeds_contentserv\Result\ContentservApiFetcherResult;
use GuzzleHttp\Exception\GuzzleException;

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
    $source = $feed->getSource();
    $url = $this->configuration['json_api_url'];
    $data_type = $this->configuration['data_type'];
    $data_url = "/core/v1/" . strtolower($data_type) . '/';
    $feed_config = $feed->getConfigurationFor($feed->getType()->getFetcher());
    $token = '';
    if (!empty($feed_config['access_token'])) {
      $token = $feed_config['access_token'];
    }

    try {
      if (empty($token)) {
        $token = $this->getAccessToken($feed, $url);
        $feed_config['access_token'] = $token;
        $feed->setConfigurationFor($feed->getType()->getFetcher(), $feed_config);
      }

      // Get the detail of product.
      $response = $this->getData($feed, $url, "$data_url{$source}", $token);
      $result = json_decode($response, TRUE);
      if (empty($result[$data_type])) {
        throw new FetchException($this->t('Faild to get the data'));
      }
    }
    catch (GuzzleException $e) {
      $args = ['%error' => $e->getMessage()];
      throw new FetchException(strtr('The error occurs while getting data because of error "%error".', $args));
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
