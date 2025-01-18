<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Result;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\feeds\Result\FetcherResult;

/**
 * The default fetcher result object.
 */
class ContentservApiFetcherResult extends FetcherResult implements ContentservApiFetcherResultInterface {

  use DependencySerializationTrait {
    __wakeup as traitWakeUp;
  }

  /**
   * Constructs an ContentservFetcherResult object.
   *
   * @param array $results
   *   An array of results.
   * @param string $accessToken
   *  The access token.
   */
  public function __construct(protected array $results, protected string $accessToken) {

  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup(): void {
    $this->traitWakeUp();

  }

  /**
   * {@inheritdoc}
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken() {
    return $this->accessToken;
  }

}
