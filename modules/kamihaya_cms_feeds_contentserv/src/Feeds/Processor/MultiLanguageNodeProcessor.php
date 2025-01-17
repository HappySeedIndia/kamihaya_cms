<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Processor;

/**
 * Defines a multi language node processor.
 *
 * Creates nodes from feed items.
 *
 * @FeedsProcessor(
 *   id = "multi_language_entity:node",
 *   title = @Translation("Node(multi language)"),
 *   description = @Translation("Creates multi language nodes from feed items."),
 *   entity_type = "node",
 *   form = {
 *     "configuration" = "Drupal\kamihaya_cms_feeds_contentserv\Feeds\Processor\Form\MultiLanguageDefaultEntityProcessorForm",
 *     "option" = "Drupal\feeds\Feeds\Processor\Form\EntityProcessorOptionForm",
 *   },
 * )
 */
class MultiLanguageNodeProcessor extends MultiLanguageEntityProcessorBase {

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
