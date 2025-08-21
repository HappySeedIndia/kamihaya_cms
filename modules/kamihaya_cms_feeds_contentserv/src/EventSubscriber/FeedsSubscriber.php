<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\feeds\Event\EntityEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\DynamicItem;
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
    $events[FeedsEvents::PARSE][] = ['afterParse', FeedsEvents::AFTER - 100];
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
   * @param bool $is_translation
   *   Whether the item is a translation.
   */
  protected function alterItem(FeedInterface $feed, ItemInterface $item, array $tampers_by_source, $is_translation = FALSE) {
    // Get the processor configuration.
    $processor_config = $feed->getType()->getProcessor()->getConfiguration();
    // Get the additional language code.
    $addtional_langcode = !empty($processor_config['addtional_langcode']) ? $processor_config['addtional_langcode'] : '';

    $tamperable_item = new TamperableFeedItemAdapter($item);

    $langcode = explode('_', $addtional_langcode)[0];
    $item_array = $item->toArray();
    foreach ($tampers_by_source as $source => $tampers) {
      try {
        if (!array_key_exists($source, $item_array)) {
          continue;
        }
        // Get the value for a source.
        $item_value = $item->get($source);

        $multiple = is_array($item_value) && !empty($item_value);
        /** @var \Drupal\tamper\TamperInterface $tamper */
        foreach ($tampers as $tamper) {
          if (!($tamper instanceof KamihayaTamperInterface) && !$is_translation) {
            continue;
          }

          $definition = $tamper->getPluginDefinition();
          if ($multiple && !$definition['handle_multiples']) {
            $new_value = [];
            // @todo throw exception if $item_value is not an array.
            foreach ($item_value as $scalar_value) {
              if ($tamper instanceof KamihayaTamperInterface) {
                $new_value[] = $tamper->postParseTamper($feed, $scalar_value, $tamperable_item);
              }
              else {
                $new_value[] = $tamper->tamper($scalar_value, $tamperable_item);
              }
            }
            $item_value = $new_value;
          }
          else {
            if ($tamper instanceof KamihayaTamperInterface) {
              $item_value = $tamper->postParseTamper($feed, $item_value, $tamperable_item);
            }
            else {
              $item_value = $tamper->tamper($item_value, $tamperable_item);
            }
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
    if ($is_translation) {
      return;
    }

    if (!empty($item->get($addtional_langcode))) {
      $this->alterItem($feed, $item->get($addtional_langcode), $tampers_by_source, TRUE);
    }
    $existing_entity_id = $this->existingEntityId($feed, $item);
    if ($existing_entity_id && $feed->getType()->getProcessor() instanceof MultiLanguageEntityProcessorBase) {
      $entity = $feed->getType()->getProcessor()->loadEntity($existing_entity_id);
      if ($entity instanceof TranslatableInterface && $entity->hasTranslation($langcode)) {
        $base_data = $item->toArray();
        unset($base_data[$addtional_langcode]);
        $data = !empty($item->get($addtional_langcode))
          ? array_merge($base_data, $item->get($addtional_langcode)->toArray())
          : $base_data;
        $translate_item = new DynamicItem();
        $translate_item->fromArray($data);
        $item->set('translation', TRUE);
        $item->set($addtional_langcode, $translate_item);
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
   * @param bool $is_translation
   *   Whether the item is a translation.
   */
  protected function alterEntity(FeedInterface $feed, EntityInterface $entity, ItemInterface $item, array $tampers_by_source, $is_translation = FALSE) {
    $tamperable_item = new TamperableFeedItemAdapter($item);
    // Get the processor configuration.
    $processor_config = $feed->getType()->getProcessor()->getConfiguration();
    // Get the additional language code.
    $addtional_langcode = !empty($processor_config['addtional_langcode']) ? $processor_config['addtional_langcode'] : '';
    $langcode = explode('_', $addtional_langcode)[0];
    foreach ($tampers_by_source as $source => $tampers) {
      /** @var \Drupal\tamper\TamperInterface $tamper */
      foreach ($tampers as $tamper) {
        if (!($tamper instanceof KamihayaTamperInterface)) {
          continue;
        }
        $tamper->preSaveTamper($feed, $entity, $tamperable_item, $source);
      }
    }
    if (!$is_translation && !empty($item->get('translation')) && !empty($item->get($addtional_langcode) && $entity instanceof TranslatableInterface) && $entity->hasTranslation($langcode)) {
      $this->alterEntity($feed, $entity->getTranslation($langcode), $item->get($addtional_langcode), $tampers_by_source);
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

  /**
   * Returns an existing entity id.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed being processed.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to find existing ids for.
   *
   * @return int|string|null
   *   The ID of the entity, or null if not found.
   */
  protected function existingEntityId(FeedInterface $feed, ItemInterface $item) {
    $feed_type = $feed->getType();
    foreach ($feed_type->getMappings() as $delta => $mapping) {
      if (empty($mapping['unique'])) {
        continue;
      }

      foreach ($mapping['unique'] as $key => $true) {
        $plugin = $feed_type->getTargetPlugin($delta);
        $entity_id = $plugin->getUniqueValue($feed, $mapping['target'], $key, $item->get($mapping['map'][$key]));
        if ($entity_id) {
          return $entity_id;
        }
      }
    }
  }

}
