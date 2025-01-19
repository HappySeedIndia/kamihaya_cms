<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Processor;

use Drupal\Core\State\StateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Feeds\Processor\EntityProcessorBase;
use Drupal\feeds\StateInterface as FeedsStateInterface;
use Drupal\feeds\StateType;

/**
 * Defines a multi language media processor.
 *
 * Creates nodes from feed items.
 *
 * @FeedsProcessor(
 *   id = "entity:empty",
 *   title = @Translation("Empty media"),
 *   description = @Translation("Creates media with other feeds"),
 *   entity_type = "file",
 *   form = {
 *     "configuration" = "Drupal\feeds\Feeds\Processor\Form\DefaultEntityProcessorForm",
 *     "option" = "Drupal\feeds\Feeds\Processor\Form\EntityProcessorOptionForm",
 *   },
 * )
 */
class EmptyProcessor extends EntityProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function entityLabel() {
    return $this->t('Media');
  }

  /**
   * {@inheritdoc}
   */
  public function entityLabelPlural() {
    return $this->t('Medias');
  }

  /**
   * {@inheritdoc}
   */
  public function process(FeedInterface $feed, ItemInterface $item, FeedsStateInterface $state) {
    $state->report(StateType::SKIP, 'This processor itself does nothing.', [
      'feed' => $feed,
      'item' => $item,
    ]);
  }

}
