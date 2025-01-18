<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Feeds\Processor\EntityProcessorBase;
use Drupal\feeds\Feeds\Target\EntityReference;

/**
 * Defines a base multi language entity processor.
 *
 * Creates entities from feed items.
 */
abstract class MultiLanguageEntityProcessorBase extends EntityProcessorBase {

  /**
   * Process multi language entity.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed object.
   * @param \Drupal\Core\Entity\EntityInterface $source_entity
   *   The source entity.
   * @param \Drupal\Core\Entity\TranslatableInterface $entity
   *   The entity to process.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to process.
   */
  public function processMultiLanguage(FeedInterface $feed, EntityInterface $source_entity, TranslatableInterface $entity, ItemInterface $item) {
    // Set field values.
    $this->mapTranslation($feed, $source_entity, $entity, $item);

    $skip_validation = !empty($this->configuration['skip_validation']) && empty($this->configuration['skip_validation_types']);
    if (!$skip_validation) {
      $this->entityValidate($entity, $feed);
    }

    // This will throw an exception on failure.
    $this->entitySaveAccess($entity);

    // Save translatiiion.
    $this->storageController->save($entity);
  }

  /**
   * Map translation.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed object.
   * @param \Drupal\Core\Entity\EntityInterface $source_entity
   *   The source entity.
   * @param \Drupal\Core\Entity\TranslatableInterface $entity
   *   The entity to process.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to process.
   */
  protected function mapTranslation(FeedInterface $feed, EntityInterface $source_entity, TranslatableInterface $entity, ItemInterface $item) {
    $mappings = $this->feedType->getMappings();

    // Mappers add to existing fields rather than replacing them. Hence we need
    // to clear target elements of each item before mapping in case we are
    // mapping on a prepopulated item such as an existing node.
    foreach ($mappings as $delta => $mapping) {
      if ($mapping['target'] === 'feeds_item') {
        // Skip feeds item as this field gets default values before mapping.
        continue;
      }
      foreach ($mapping['map'] as $column => $source) {
        if ($source === '') {
          // Skip empty sources.
          continue;
        }

        $value = $item->get($source);
        if (is_null($value)) {
          // Skip NULL values.
          continue;
        }
        if (!isset($source_values[$delta][$column])) {
          $source_values[$delta][$column] = [];
        }
        if (!is_array($value)) {
          $source_values[$delta][$column][] = $value;
        }
        else {
          $source_values[$delta][$column] = array_merge($source_values[$delta][$column], $value);
        }
      }
      // Clear the target.
      if (empty($source_values[$delta])) {
        continue;
      }
      if ($this->feedType->getTargetPlugin($delta) instanceof EntityReference) {
        $value = $source_entity->get($mapping['target'])->getValue();
        if (!empty($value)) {
          $entity->set($mapping['target'], $value);
          unset($source_values[$delta]);
          continue;
        }
      }
      $this->feedType->getTargetPlugin($delta)->clearTarget($feed, $entity, $mapping['target']);
    }

    // Rearrange values into Drupal's field structure.
    $field_values = [];
    foreach ($source_values as $field => $field_value) {
      $field_values[$field] = [];
      foreach ($field_value as $column => $values) {
        // Use array_values() here to keep our $delta clean.
        foreach (array_values($values) as $delta => $value) {
          $field_values[$field][$delta][$column] = $value;
        }
      }
    }

    // Set target values.
    foreach ($mappings as $delta => $mapping) {
      $plugin = $this->feedType->getTargetPlugin($delta);

      // Skip immutable targets for which the entity already has a value.
      if (!$plugin->isMutable() && !$plugin->isEmpty($feed, $entity, $mapping['target'])) {
        continue;
      }

      if (isset($field_values[$delta])) {
        $plugin->setTarget($feed, $entity, $mapping['target'], $field_values[$delta]);
      }
    }
    return $entity;
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = parent::defaultConfiguration();
    $entity_type = $this->entityType;

    if (!$entity_type->hasKey('langcode') || !in_array($entity_type->id(), ['node', 'media', 'file']) || empty($defaults['langcode'])) {
      return $defaults;
    }

    $defaults['addtional_langcode'] = '';
    return $defaults;
  }

}
