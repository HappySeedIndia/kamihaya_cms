<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Result;

use Drupal\feeds\Result\FetcherResultInterface;

/**
 * Defines the interface for result objects returned by Contentserv API fetchers.
 */
interface ContentservApiFetcherResultInterface extends FetcherResultInterface {

  /**
   * Returns the results.
   *
   * @return array
   *   The results array.
   */
  public function getResults();

  /**
   * Returns the access token.
   *
   * @return string
   *   The access token.
   */
  public function getAccessToken();

}
