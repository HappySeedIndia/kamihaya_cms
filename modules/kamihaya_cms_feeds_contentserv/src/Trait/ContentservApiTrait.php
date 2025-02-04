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
  public function getAccessToken(FeedInterface $feed, $url, $retry = FALSE) {
    // Get the fetcher configuration.
    $fetcher_config = $feed->getType()->getFetcher()->getConfiguration();
    // Set the options.
    $options = [
      RequestOptions::TIMEOUT => $fetcher_config['request_timeout'],
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/json',
        'Authorization' => "CSAuth {$fetcher_config['api_key']}:{$fetcher_config['secret']}",
      ],
      RequestOptions::HTTP_ERRORS => FALSE,
    ];
    $auth_url = "$url/auth/v1/token";
    // Get the access token.
    $response = $this->httpClient->get($auth_url, $options);
    $status_code = $response->getStatusCode();
    if ($status_code == 429 && !$retry) {
      // Retry if the status code is 429.
      $sleep_seconds = !empty($response->getHeader('Retry-After')[0]) ? $response->getHeader('Retry-After')[0] : 30;
      // Sleep the seconds which is set in the header as Retry-After.
      sleep($sleep_seconds);
      // Retry to get the access token.
      return $this->getAccessToken($feed, $url);
    }
    if ($status_code != 200) {
      // Throw the exception if the status code is not 200.
      throw new FetchException($this->t('Faild to get the access token'));
    }
    $result = json_decode($response->getBody()->getContents(), TRUE);
    $token = !empty($result['access_token']) ? $result['access_token'] : '';
    if (empty($token)) {
      // Throw the exception if the access token is empty.
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
   * @param bool $retry
   *   The flag to retry.
   */
  public function getData(FeedInterface $feed, string $bace_url, string $additional_url, string $token, array $options = [], $retry = FALSE) {
    // Get the fetcher configuration.
    $fetcher_config = $feed->getType()->getFetcher()->getConfiguration();
    // Set the options.
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
      // Get the new access token if the status code is 401 or 403.
      $token = $this->getAccessToken($feed, $bace_url);
      $feed_config = $feed->getConfigurationFor($feed->getType()->getFetcher());
      if (!empty($feed_config['access_token'])) {
        // Set the new access token to the feed configuration.
        $feed_config['access_token'] = $token;
        $feed->setConfigurationFor($feed->getType()->getFetcher(), $feed_config);
      }
      // Set the new access token to the headers.
      $options[RequestOptions::HEADERS]['Authorization'] = "Bearer $token";
      // Get the data with the new access token.
      $response = $this->httpClient->get("{$bace_url}{$additional_url}", $options);
      $status_code = $response->getStatusCode();
    }
    if ($status_code == 429 && !$retry) {
      // Retry if the status code is 429.
      $sleep_seconds = !empty($response->getHeader('Retry-After')[0]) ? $response->getHeader('Retry-After')[0] : 30;
      // Sleep the seconds which is set in the header as Retry-After.
      sleep($sleep_seconds);
      // Retry to get the data.
      return $this->getData($feed, $bace_url, $additional_url, $token, $options, TRUE);
    }
    if ($status_code != 200) {
      // Throw the exception if the status code is not 200.
      throw new FetchException($this->t('Faild to get the data'));
    }
    return $response->getBody()->getContents();
  }

  /**
   * Check if the feed has 'Tags' in source.
   *
   * @param FeedInterface $feed
   *   The feed object.
   * @return bool
   *   TRUE if the feed has 'Tags' in source, FALSE otherwise.
   */
  public function hasTagsInSource(FeedInterface $feed) {
    foreach ($feed->getType()->getMappingSources() as $key => $info) {
      if (!empty($info['value']) && trim(strval($info['value'])) === 'Tags') {
        return TRUE;
      }
    }
    return FALSE;
  }
}
