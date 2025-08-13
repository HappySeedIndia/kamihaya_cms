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
   * {@inheritdoc}
   */
  protected function map(FeedInterface $feed, EntityInterface $entity, ItemInterface $item) {
    // Update multi value fields before mapping.
    $this->updateMultiValueFields($item);

    parent::map($feed, $entity, $item);

    // Set default values to empty fields that have default values.
    $this->setDefaultValueToEmptyFeild($entity, $item);
  }


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
    // Update multi value fields before mapping.
    $this->updateMultiValueFields($item);

    // Set field values.
    $this->mapTranslation($feed, $source_entity, $entity, $item);

    // Set default values to empty fields that have default values.
    $this->setDefaultValueToEmptyFeild($entity, $item);

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
   * Load entity.
   *
   * @param string $entity_id
   *   The entity id.
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity object.
   */
  public function loadEntity($entity_id) {
    return $this->storageController->load($entity_id);
  }

  /**
   * Update multi value fields.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed object.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to process.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to process.
   */
  protected function updateMultiValueFields(ItemInterface $item) {
    $mappings = $this->feedType->getMappings();

    $multivalue_fields = [];
    foreach ($mappings as $delta => $mapping) {
      if ($mapping['target'] === 'feeds_item' || $mapping['target'] === 'temporary_target') {
        // Skip feeds item as this field gets default values before mapping.
        continue;
      }
      $target = $mapping['target'];
      if (empty($multivalue_fields[$target])) {
        $multivalue_fields[$target] = [];
      }
      $multivalue_fields[$target][] = $mapping['map'];
    }

    foreach ($multivalue_fields as $target => $field_mappings) {
      if (count($field_mappings) <= 1) {
        // If there is only one mapping for this target, skip it.
        continue;
      }
      $field_values = [];
      foreach ($field_mappings as $mapping) {
        foreach ($mapping as $column => $source) {
          if ($source === '') {
            // Skip empty sources.
            continue;
          }

          $value = $item->get($source);

          // Clear the source value in the item to avoid duplication.
          $item->set($source, NULL);
          if (is_null($value)) {
            // Skip NULL values.
            continue;
          }
          if (empty($field_values[$column])) {
            $field_values[$column] = [];
          }
          if (!is_array($value)) {
            $field_values[$column][] = $value;
          }
          else {
            $field_values[$column] = array_merge($field_values[$column], $value);
          }
        }
      }
      if (empty($field_values)) {
        continue;
      }
      $mapping = reset($field_mappings);
      foreach ($mapping as $column => $source) {
        if ($source === '') {
          // Skip empty sources.
          continue;
        }
        // Set the field value to the item.
        $item->set($source, $field_values[$column]);
      }
    }
  }

  /**
   * Unset empty fields that have default values.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to process.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to process.
   *
   * @return \Drupal\feeds\Feeds\Item\ItemInterface
   *   The item with unset empty fields.
   */
  protected function setDefaultValueToEmptyFeild(EntityInterface $entity, ItemInterface $item) {
    // Get the mappings.
    $mappings = $this->feedType->getMappings();
    foreach ($mappings as $delta => $mapping) {
      if ($mapping['target'] === 'feeds_item' || $mapping['target'] === 'temporary_target') {
        // Skip feeds item as this field gets default values before mapping.
        continue;
      }
      foreach ($mapping['map'] as $column => $source) {
        if ($source === '') {
          // Skip empty sources.
          continue;
        }
        // Check if the entity already has a value.
        $is_value_empty = FALSE;

        if (empty($entity->get($mapping['target'])->getValue())) {
          $is_value_empty = TRUE;
        }
        else {
          // Check if the entity already has a value.
          $entity_value = reset($entity->get($mapping['target'])->getValue());
          if (isset($entity_value['value']) && $entity_value['value'] !== '') {
            continue;
          }
          if (isset($entity_value['target_id'])) {
            continue;
          }
          if (isset($entity_value['uri']) && !empty($entity_value['uri'])) {
            continue;
          }
          $is_value_empty = TRUE;
        }

        if (!$is_value_empty) {
          // Skip if the entity already has a value.
          continue;
        }
        $field_definition = $entity->getFieldDefinition($mapping['target']);
        $default_value = $field_definition ? $field_definition->getDefaultValue($entity) : NULL;
        if (is_null($default_value) || (is_array($default_value) && empty($default_value)) || (is_string($default_value) && strlen($default_value) === 0)) {
          continue;
        }
        $entity->set($mapping['target'], $default_value);
      }
    }
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
