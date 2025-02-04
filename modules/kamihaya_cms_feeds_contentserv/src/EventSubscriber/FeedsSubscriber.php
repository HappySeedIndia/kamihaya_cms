<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\feeds\Event\EntityEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds_tamper\Adapter\TamperableFeedItemAdapter;
use Drupal\feeds_tamper\FeedTypeTamperManagerInterface;
use Drupal\kamihaya_cms_feeds_contentserv\Feeds\Processor\MultiLanguageEntityProcessorBase;
use Drupal\kamihaya_cms_feeds_contentserv\Plugin\Tamper\KamihayaTamperInterface;
use Drupal\tamper\Exception\SkipTamperDataException;
use Drupal\tamper\Exception\SkipTamperItemException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber to Feeds events.
 */
class FeedsSubscriber implements EventSubscriberInterface {

  public function __construct(protected FeedTypeTamperManagerInterface $tamperManager) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[FeedsEvents::PARSE][] = ['afterParse', FeedsEvents::AFTER];
    $events[FeedsEvents::PROCESS_ENTITY_PRESAVE][] = 'preSave';
    $events[FeedsEvents::PROCESS_ENTITY_POSTSAVE][] = 'postSave';
    return $events;
  }

    /**
   * Acts on parser result.
   */
  public function afterParse(ParseEvent $event) {
    /** @var \Drupal\feeds\FeedInterface $feed */
    $feed = $event->getFeed();

    /** @var \Drupal\feeds_tamper\FeedTypeTamperMetaInterface $tamper_meta */
    $tamper_meta = $this->tamperManager->getTamperMeta($feed->getType());

    // Load the tamper plugins that need to be applied to Feeds.
    $tampers_by_source = $tamper_meta->getTampersGroupedBySource();

    // Abort if there are no tampers to apply on the current feed.
    if (empty($tampers_by_source)) {
      return;
    }

    /** @var \Drupal\feeds\Result\ParserResultInterface $result */
    $result = $event->getParserResult();

    for ($i = 0; $i < $result->count(); $i++) {
      if (!$result->offsetExists($i)) {
        break;
      }

      /** @var \Drupal\feeds\Feeds\Item\ItemInterface $item */
      $item = $result->offsetGet($i);

      try {
        $this->alterItem($feed, $item, $tampers_by_source);
      }
      catch (SkipTamperItemException $e) {
        $result->offsetUnset($i);
        $i--;
      }
    }
  }

  /**
   * Alters a single item.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   * @param \Drupal\tamper\TamperInterface[][] $tampers_by_source
   *   A list of tampers to apply, grouped by source.
   */
  protected function alterItem(FeedInterface $feed, ItemInterface $item, array $tampers_by_source) {
    $tamperable_item = new TamperableFeedItemAdapter($item);
    foreach ($tampers_by_source as $source => $tampers) {
      try {
        // Get the value for a source.
        $item_value = $item->get($source);
        $multiple = is_array($item_value) && !empty($item_value);

        /** @var \Drupal\tamper\TamperInterface $tamper */
        foreach ($tampers as $tamper) {
          if (!($tamper instanceof KamihayaTamperInterface)) {
            continue;
          }
          $definition = $tamper->getPluginDefinition();
          if ($multiple && !$definition['handle_multiples']) {
            $new_value = [];
            // @todo throw exception if $item_value is not an array.
            foreach ($item_value as $scalar_value) {
              $new_value[] = $tamper->postParseTamper($feed, $scalar_value, $tamperable_item);
            }
            $item_value = $new_value;
          }
          else {
            $item_value = $tamper->postParseTamper($feed, $item_value, $tamperable_item);
            $multiple = $tamper->multiple();
          }
        }

        // Write the changed value.
        $item->set($source, $item_value);
      }
      catch (SkipTamperDataException $e) {
        $item->set($source, NULL);
      }
    }
  }

  /**
   * Acts on presaving an entity.
   */
  public function preSave(EntityEvent $event) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $event->getEntity();

    $item = $event->getItem();

    /** @var \Drupal\feeds\FeedInterface $feed */
    $feed = $event->getFeed();

    /** @var \Drupal\feeds_tamper\FeedTypeTamperMetaInterface $tamper_meta */
    $tamper_meta = $this->tamperManager->getTamperMeta($feed->getType());

    // Load the tamper plugins that need to be applied to Feeds.
    $tampers_by_source = $tamper_meta->getTampersGroupedBySource();

    // Abort if there are no tampers to apply on the current feed.
    if (empty($tampers_by_source)) {
      return;
    }

    $this->alterEntity($feed, $entity, $item, $tampers_by_source);
  }

  /**
   * Alters a single item.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.@param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to alter.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to alter.
   * @param \Drupal\tamper\TamperInterface[][] $tampers_by_source
   *   A list of tampers to apply, grouped by source.
   */
  protected function alterEntity(FeedInterface $feed, EntityInterface $entity, ItemInterface $item, array $tampers_by_source) {
    $tamperable_item = new TamperableFeedItemAdapter($item);
    foreach ($tampers_by_source as $source => $tampers) {
      /** @var \Drupal\tamper\TamperInterface $tamper */
      foreach ($tampers as $tamper) {
        if (!($tamper instanceof KamihayaTamperInterface)) {
          continue;
        }
        $tamper->preSaveTamper($feed, $entity, $tamperable_item, $source);
      }
    }
  }

  /**
   * Acts on parser result.
   */
  public function postSave(EntityEvent $event) {
    /** @var \Drupal\feeds\FeedInterface $feed */
    $feed = $event->getFeed();
    $processor = $feed->getType()->getProcessor();

    if (!($processor instanceof MultiLanguageEntityProcessorBase)) {
      return;
    }

    $entity = $event->getEntity();
    if (!($entity instanceof TranslatableInterface)) {
      return;
    }

    $item = $event->getItem();
    $processor_config = $feed->getType()->getProcessor()->getConfiguration();
    $addtional_langcode = !empty($processor_config['addtional_langcode']) ? $processor_config['addtional_langcode'] : '';

    if (empty($addtional_langcode) || empty($item->get($addtional_langcode))) {
      return;
    }

    $langcode = explode('_', $addtional_langcode)[0];
    $lang_items = $item->get($addtional_langcode);

    if (!$entity->hasTranslation($langcode)) {
      $source_langcode = $entity->language()->getId();
      $values = $entity->toArray();
      $translation = $entity->addTranslation($langcode, $values);
      $class = $translation->getEntityType()->get('content_translation_metadata');
      $handler = \Drupal::entityTypeManager()->getHandler($translation->getEntityTypeId(), 'translation');
      $metadata = new $class($translation, $handler);
      $metadata->setAuthor($translation->getOwner());
      $metadata->setCreatedTime($translation->getCreatedTime());
      $metadata->setSource($source_langcode);
    }
    else {
      $translation = $entity->getTranslation($langcode);
    }
    $processor->processMultiLanguage($feed, $entity, $translation, $lang_items);
  }

}
