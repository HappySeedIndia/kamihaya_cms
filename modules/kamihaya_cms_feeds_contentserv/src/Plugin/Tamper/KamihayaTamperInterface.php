<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Plugin\Tamper;

use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds\FeedInterface;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperInterface;

/**
 * Interface definition for tamper plugins.
 */
interface KamihayaTamperInterface extends TamperInterface {

  /**
   * Tamper pre save data.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   * @param mixed $data
   *   The data to tamper.
   * @param \Drupal\tamper\TamperableItemInterface $item
   *  The item to alter.
   */
  public function postParseTamper(FeedInterface $feed, $data, TamperableItemInterface $item);

  /**
   * Tamper pre save data.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to alter.
   * @param \Drupal\tamper\TamperableItemInterface $item
   *  The item to alter.
   * @param string $source
   *   The source name to alter.
   */
  public function preSaveTamper(FeedInterface $feed, EntityInterface $entity, TamperableItemInterface $item, $source);

}
