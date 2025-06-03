<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Service;

use Drupal\feeds\FeedInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Contentserv API Client.
 */
class ContentservClient {


  /**
   * Constructs an ExabaseClient object.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected LoggerInterface $logger
  ) {
  }

  /**
   * Makes a request to the Contentserv API.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed object.
   * @param string $url
   *   The URL to request.
   * @param array $options
   *   The options for the request.
   */
  public function request(FeedInterface $feed, string $url, array $options = [], $retry_count = 0) {
    // Get the fetcher.
    $fetcher = $feed->getType()->getFetcher();
    // Get the fetcher configuration.
    $fetcher_config = $fetcher->getConfiguration();
    $default_config = $fetcher->defaultConfiguration();
    $max_retry_count = $fetcher_config['retry_count'] ?? $default_config['retry_count'] ?? 5;
    $retry_delay = $fetcher_config['retry_delay'] ?? $default_config['retry_delay'] ?? 10;
    try {
      $response = $this->httpClient->get($url, $options);
      $status_code = $response->getStatusCode();
      if (($status_code == 400 || $status_code > 500) && $retry_count < $max_retry_count) {
        // Retry after a delay if the status code is 500 or higher.
        $this->logger->warning('Request failed with status code @status_code. Retrying in @delay seconds.', [
          '@status_code' => $status_code,
          '@delay' => $retry_delay,
        ]);
        sleep($retry_delay);
        return $this->request($feed, $url, $options, $retry_count + 1);
      }
      return $response;
    }
    catch (RequestException $e) {
      if ($retry_count < $max_retry_count) {
        // Retry after a delay if the status code is 500 or higher.
        $this->logger->warning('Request failed with exception: @message. Retrying in @delay seconds.', [
          '@message' => $e->getMessage(),
          '@delay' => $retry_delay,
        ]);
        sleep($retry_delay);
        return $this->request($feed, $url, $options, $retry_count + 1);
      }
      $this->logger->error('Request failed with exception: @message', [
        '@message' => $e->getMessage(),
      ]);
      throw $e; // Re-throw the exception after max retries.
    }
  }
}
