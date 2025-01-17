<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Plugin\Tamper;

use Drupal\Core\Entity\EntityInterface;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperInterface;

/**
 * Interface definition for tamper plugins.
 */
interface KamihayaTamperInterface extends TamperInterface {

  /**
   * Tamper pre save data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to alter.
   * @param \Drupal\tamper\TamperableItemInterface $item
   *  The item to alter.
   * @param string $source
   *   The source name to alter.
   */
  public function preSavetamper(EntityInterface $entit, TamperableItemInterface $item = NULL, $source);

}
