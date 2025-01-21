<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Trait;

use Drupal\feeds\Exception\FetchException;
use Drupal\feeds\FeedInterface;
use GuzzleHttp\RequestOptions;

/**
 * Defines an Contentserv API trait.
 */
trait ContentservApiTrait {

  /**
   * Get the access token.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed object.
   * @param string $url
   *   The url to get the access token.
   * @return string
   *   The access token.
   */
  public function getAccessToken(FeedInterface $feed, $url) {
    $fetcher_config = $feed->getType()->getFetcher()->getConfiguration();
    $options = [
      RequestOptions::TIMEOUT => $fetcher_config['request_timeout'],
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/json',
        'Authorization' => "CSAuth {$fetcher_config['api_key']}:{$fetcher_config['secret']}",
      ],
    ];
    $auth_url = "$url/auth/v1/token";
    // Get the access token.
    $response = $this->httpClient->get($auth_url, $options);
    $result = json_decode($response->getBody()->getContents(), TRUE);
    $token = !empty($result['access_token']) ? $result['access_token'] : '';
    if (empty($token)) {
      throw new FetchException($this->t('Faild to get the access token'));
    }
    return $token;
  }

  /**
   * Get the data with Contentserv API.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed object.
   * @param string $u$bace_urlrl
   *   The base url to get the data.
   * @param string $additional_url
   *   The additional url to get the data.
   * @param string $token
   *   The access token.
   * @param array $options
   *   The options to get the data.
   */
  public function getData(FeedInterface $feed, string $bace_url, string $additional_url, string $token, array $options = []) {
    $fetcher_config = $feed->getType()->getFetcher()->getConfiguration();
    $options += [
      RequestOptions::TIMEOUT => $fetcher_config['request_timeout'],
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/json',
        'Authorization' => "Bearer $token",
      ],
      RequestOptions::HTTP_ERRORS => FALSE,
    ];
    // Get the access token.
    $response = $this->httpClient->get("{$bace_url}{$additional_url}", $options);
    $status_code = $response->getStatusCode();
    if ($status_code == 401 || $status_code == 403) {
      $token = $this->getAccessToken($feed, $bace_url);
      $feed_config = $feed->getConfigurationFor($feed->getType()->getFetcher());
      if (!empty($feed_config['access_token'])) {
        $feed_config['access_token'] = $token;
        $feed->setConfigurationFor($feed->getType()->getFetcher(), $feed_config);
      }
      $options[RequestOptions::HEADERS]['Authorization'] = "Bearer $token";
      // Get the data with the new access token.
      $response = $this->httpClient->get("{$bace_url}{$additional_url}", $options);
      $status_code = $response->getStatusCode();
    }
    if ($status_code !== 200) {
      throw new FetchException($this->t('Faild to get the data'));
    }
    return $response->getBody()->getContents();
  }

}
