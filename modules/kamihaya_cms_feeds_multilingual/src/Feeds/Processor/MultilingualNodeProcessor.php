<?php

namespace Drupal\kamihaya_cms_feeds_multilingual\Feeds\Processor;

/**
 * Defines a multilingual node processor.
 *
 * Creates nodes from feed items.
 *
 * @FeedsProcessor(
 *   id = "multilingual_entity:node",
 *   title = @Translation("Node(multilingual)"),
 *   description = @Translation("Creates multilingual nodes from feed items."),
 *   entity_type = "node",
 *   form = {
 *     "configuration" = "Drupal\feeds\Feeds\Processor\Form\DefaultEntityProcessorForm",
 *     "option" = "Drupal\feeds\Feeds\Processor\Form\EntityProcessorOptionForm",
 *   },
 * )
 */
class MultilingualNodeProcessor extends MultilingualEntityProcessor {

  /**
   * {@inheritdoc}
   */
  public function entityLabel() {
    return $this->t('Node');
  }

  /**
   * {@inheritdoc}
   */
  public function entityLabelPlural() {
    return $this->t('Nodes');
  }

}
