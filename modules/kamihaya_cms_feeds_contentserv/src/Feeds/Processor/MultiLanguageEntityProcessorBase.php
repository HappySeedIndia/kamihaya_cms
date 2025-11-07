<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Feeds\Processor\EntityProcessorBase;

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
    $this->setDefaultValueToEmptyFeild($entity);
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

    $this->autoCreateEntityReferenceTranslation($feed, $source_entity, $item);

    // Set new revision if needed.
    if ($this->configuration['revision']) {
      $entity->setNewRevision(TRUE);
      $entity->setRevisionCreationTime($this->dateTime->getRequestTime());
    }

    // Set field values.
    $this->mapTranslation($feed, $source_entity, $entity, $item);

    // Set default values to empty fields that have default values.
    $this->setDefaultValueToEmptyFeild($entity);

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
   *
   */
  protected function setDefaultValueToEmptyFeild(EntityInterface $entity) {
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

        $entity_values = $entity->get($mapping['target'])->getValue();
        if (empty($entity_values)) {
          $is_value_empty = TRUE;
        }
        else {
          // Check if the entity already has a value.
          $entity_value = reset($entity_values);
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
   * Auto create entity reference translation.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed object.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to process.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to process.
   */
  protected function autoCreateEntityReferenceTranslation(FeedInterface $feed, EntityInterface $entity, ItemInterface $item) {
    if (!($entity instanceof TranslatableInterface) || !$entity->isTranslatable()) {
      return;
    }
    // Get the mappings.
    $mappings = $this->feedType->getMappings();
    // Get the processor configuration.
    $processor_config = $feed->getType()->getProcessor()->getConfiguration();
    $addtional_langcode = !empty($processor_config['addtional_langcode']) ? $processor_config['addtional_langcode'] : '';
    if (empty($addtional_langcode)) {
      return;
    }
    $langcode = explode('_', $addtional_langcode)[0];

    foreach ($mappings as $delta => $mapping) {
      // Skip mappings that are not auto create or do not have a target.
      if (empty($mapping['settings']['autocreate']) || empty($mapping['map']['target_id'])) {
        continue;
      }

      $value = $item->get($mapping['map']['target_id']);
      if (is_null($value) || (is_array($value) && (empty($value) || !array_filter($value))) || $value === '') {
        // Skip NULL values.
        continue;
      }
      $values = !is_array($value) ? [$value] : $value;

      $field_definition = $entity->getFieldDefinition($mapping['target']);
      if (empty($field_definition)) {
        continue;
      }
      $target_type = $field_definition->getSetting('target_type');
      if (empty($target_type)) {
        continue;
      }
      // Gnet the entity type from the field definition.
      $entity_type = $this->entityTypeManager->getDefinition($target_type);
      // Get the keys from the entity type.
      $key = $mapping['settings']['reference_by'] ?? 'name';
      $bundle_key = $entity_type->getKey('bundle') ?? 'bundle';

      $default_values = $entity->get($mapping['target'])->getValue();
      $target_ids = [];
      if (!empty($default_values)) {
        $target_ids = array_column($default_values, 'target_id');
      }

      if (count($values) > 1 || count($target_ids) > 1) {
        foreach($values as $idx => $value) {
          // Check if the translated entity already exists.
          $translated_entity = $this->getAutoCreateEntityTranslation(
            $target_type,
            $mapping['settings']['autocreate_bundle'],
            $key,
            $value,
            $langcode
          );

          if (!empty($translated_entity)) {
            continue;
          }

          if (empty($target_ids[$idx])) {
            $translated_entity = $this->entityTypeManager->getStorage($target_type)->create([
              'langcode' => $langcode,
              'status' => 1,
              $bundle_key => $mapping['settings']['autocreate_bundle'],
              $key => $value,
            ]);

            $translated_entity->setNewRevision(TRUE);
            $translated_entity->save();
            continue;
          }
          $default_entity = $this->entityTypeManager->getStorage($target_type)->load($target_ids[$idx]);
          if (empty($default_entity)) {
            continue;
          }
          $default_value = $default_entity->get($mapping['settings']['reference_by'])->getValue();
          if (empty($default_value)) {
            continue;
          }
          $values[$idx] = $default_value[0]['value'] ?? $default_value[0]['target_id'] ?? $default_value[0]['uri'] ?? '';
        }

        $item->set($mapping['map']['target_id'], $values);
        return;
      }

      // If there is only one value, we can process it directly.
      $value = reset($values);
      $translated_entity = $this->getAutoCreateEntityTranslation(
        $target_type,
        $mapping['settings']['autocreate_bundle'],
        $key,
        $value,
        $langcode
      );

      if (!empty($translated_entity)) {
        // If the entity already exists, skip it.
        continue;
      }

      $referenced_entity = NULL;
      if (!empty($target_ids)) {
        $default_entity = $this->entityTypeManager->getStorage($target_type)->load($target_ids[0]);
        $default_value = $default_entity->get($mapping['settings']['reference_by'])->getValue();
        $default_value = !empty($default_value) && !empty($default_value[0]['value']) ? $default_value[0]['value'] : '';
        if (!empty($default_value) && $default_value === $value) {
          // If the default entity already has the value, we can use it.
          return;
        }
        if (!empty($default_entity) && $default_entity->hasTranslation($langcode)) {
          $translated_entity = $default_entity->getTranslation($langcode);
          if ($translated_entity->get($mapping['settings']['reference_by'])->getValue() === $value) {
            // If the translated entity already has the value, we can use it.
            return;
          }
          // If the default entity has a translation for the given language code,
          // we can use it.
          $item->set($mapping['map']['target_id'], (is_array($value) ? [$default_value] : $default_value));
          continue;
        }
        if (!empty($default_entity) && !$default_entity->hasTranslation($langcode)) {
          $referenced_entity = $default_entity->addTranslation($langcode);
        }
      }
      if (empty($referenced_entity)) {
        // Create the referenced entity with the additional language code.
        $referenced_entity = $this->entityTypeManager->getStorage($target_type)->create([
          'langcode' => $langcode,
          'status' => 1,
          $bundle_key => $mapping['settings']['autocreate_bundle'],
        ]);
      }
      if (empty($referenced_entity)) {
        // If the referenced entity is still empty, skip it.
        continue;
      }
      // Set the value to the translated referenced entity.
      $referenced_entity->set($key, $value);
      $referenced_entity->setNewRevision(TRUE);
      $referenced_entity->save();
    }
  }

  /**
   * Get entity translation.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $key
   *   The key to search for.
   * @param string $value
   *   The value to search for.
   * @param string $langcode
   *   The language code.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity translation or NULL if not found.
   */
  protected function getAutoCreateEntityTranslation($entity_type, $bundle, $key, $value, $langcode) {
    // Get the entity type definition.
    $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type);
    // Get the bundle key for the entity type.
    $bundle_key = $entity_type_definition->getKey('bundle');
    // Load the entity by properties.
    $ids = $this->entityTypeManager->getStorage($entity_type)->getQuery()
      ->condition($key, $value, '=', $langcode)
      ->condition($bundle_key, $bundle)
      ->accessCheck(FALSE)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }
    $id = reset($ids);
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($id);
    // Check if the entity has a translation for the given language code.
    if (empty($entity)) {
      return NULL;
    }

    // If the entity is already in the requested language, return it.
    if ($entity->language()->getId() === $langcode) {
      return $entity;
    }
    // If the entity does not have a translation for the given language code,
    // return NULL.
    if (!$entity->hasTranslation($langcode)) {
      return NULL;
    }
    // Return the translation for the given language code.
    return $entity->getTranslation($langcode);
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
