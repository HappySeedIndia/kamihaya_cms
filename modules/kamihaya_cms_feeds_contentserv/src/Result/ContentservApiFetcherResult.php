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
   * @param array $headers
   *   An array of HTTP headers.
   */
  public function __construct(protected array $products, protected string $accessToken) {

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
  public function getProducts() {
    return $this->products;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken() {
    return $this->accessToken;
  }

}
