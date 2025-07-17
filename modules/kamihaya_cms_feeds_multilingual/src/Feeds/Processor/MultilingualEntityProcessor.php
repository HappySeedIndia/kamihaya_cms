<?php

namespace Drupal\kamihaya_cms_feeds_multilingual\Feeds\Processor;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Validation\ConstraintManager;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\feeds\Feeds\Processor\EntityProcessorBase;
use Drupal\feeds\StateInterface;
use Drupal\feeds\StateType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a multilingual entity processor for all entity types.
 *
 * @FeedsProcessor(
 *   id = "multilingual_entity",
 *   title = @Translation("Multilingual Entity processor"),
 *   description = @Translation("Creates and updates entities with multilingual support for all entity types."),
 *   form = {
 *     "configuration" = "Drupal\feeds\Feeds\Processor\Form\DefaultEntityProcessorForm",
 *     "option" = "Drupal\feeds\Feeds\Processor\Form\EntityProcessorOptionForm",
 *   },
 *   deriver = "Drupal\kamihaya_cms_feeds_multilingual\Plugin\Derivative\GenericMultilingualEntityProcessor",
 * )
 */
class MultilingualEntityProcessor extends EntityProcessorBase {

    /**
   * Constructs an EntityProcessorBase object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Component\Datetime\TimeInterface $date_time
   *   The datetime service for getting the system time.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $action_manager
   *   The action plugin manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger for the feeds channel.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\Core\Validation\ConstraintManager $constraint_manager
   *   The validation constraint manager.
   * @param \Drupal\Core\Config\ConfigInstallerInterface $config_installer
   *   The config installer, used to check if config is syncing.
   */
  public function __construct(array $configuration,
    $plugin_id,
    array $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    LanguageManagerInterface $language_manager,
    TimeInterface $date_time,
    PluginManagerInterface $action_manager,
    RendererInterface $renderer,
    LoggerInterface $logger,
    Connection $database,
    ConstraintManager $constraint_manager,
    ConfigInstallerInterface $config_installer,
    protected EntityFieldManagerInterface $entityFieldManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_type_bundle_info, $language_manager, $date_time, $action_manager, $renderer, $logger, $database, $constraint_manager, $config_installer);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('language_manager'),
      $container->get('datetime.time'),
      $container->get('plugin.manager.action'),
      $container->get('renderer'),
      $container->get('logger.factory')->get('feeds'),
      $container->get('database'),
      $container->get('validation.constraint'),
      $container->get('config.installer'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(FeedInterface $feed, ItemInterface $item, StateInterface $state) {
    // Initialize clean list if needed.
    $clean_state = $feed->getState(StateInterface::CLEAN);
    if (!$clean_state->initiated()) {
      $this->initCleanList($feed, $clean_state);
    }

    $skip_new = $this->configuration['insert_new'] == static::SKIP_NEW;
    $skip_existing = $this->configuration['update_existing'] == static::SKIP_EXISTING;

    $mappings = $feed->getType()->getMappings();

    // Get langcode key, translation key map and translation key target from feeds mapping.
    foreach ($mappings as $mapping) {
      if (empty($mapping['target']) || empty($mapping['map']['value'])) {
        continue;
      }

      if ($mapping['target'] === 'langcode') {
        $langcode_target = $mapping['map']['value'];
        continue;
      }

      if ($mapping['target'] !== 'translation_key' || empty($mapping['settings']['key_target'])) {
        continue;
      }
      $translation_key_map = $mapping['map']['value'];
      $translation_key_target = $mapping['settings']['key_target'];
    }

    if (empty($langcode_target) || empty($translation_key_map) || empty($translation_key_target)) {
      $this->messenger()->addError(t('Missing required mappings for language code or translation key.'));
      return;
    }

    // Get language identifier field value
    $language_code = $item->get($langcode_target);

    // Get translation key field value
    $translation_key = $item->get($translation_key_map);

    // Validate language code
    if (!$this->isValidLanguageCode($language_code)) {
      $this->messenger()->addError(t('Invalid language code: @code', ['@code' => $language_code]));
      return;
    }

    // Search for existing entity
    $existing_entity = $this->findExistingEntity($translation_key_target, $translation_key);

    // If the entity is an existing entity it must be removed from the clean
    // list.
    if ($existing_entity) {
      $clean_state->removeItem($existing_entity->id());
    }

    if ($existing_entity && $skip_existing && ($existing_entity->language()->getId() === $language_code || $existing_entity->hasTranslation($language_code))) {
      // Skip the item if the entity already existing and when we are not updating
      // existing entities.
      $state->report(StateType::SKIP, 'Skipped because the entity already exists.', [
        'feed' => $feed,
        'item' => $item,
        'entity_label' => [$existing_entity_id, 'id'],
      ]);
      return;
    }

    if ($skip_new && (!$existing_entity || ($existing_entity && $existing_entity->language()->getId() !== $language_code && !$existing_entity->hasTranslation($language_code)))) {
      // Skip the item if the entity does not exist and when we are not creating
      // new entities.
      $state->report(StateType::SKIP, 'Skipped because new entities should not be created.', [
        'feed' => $feed,
        'item' => $item,
      ]);
      return;
    }

    $hash = $this->hash($item);
    $translation_entity = $existing_entity && $existing_entity->language()->getId() !== $language_code && $existing_entity->hasTranslation($language_code)
      ? $existing_entity->getTranslation($language_code)
      : NULL;
    $changed = !empty($translation_entity)
      ? $translation_entity && ($hash !== $translation_entity->get('feeds_item')->getItemHashByFeed($feed))
      : $existing_entity && ($hash !== $existing_entity->get('feeds_item')->getItemHashByFeed($feed));

    // Do not proceed if the item exists, has not changed, and we're not
    // forcing the update.
    if ($this->configuration['skip_hash_check']) {
      if ($translation_entity && !$changed) {
        $state->report(StateType::SKIP, 'Skipped because the source data has not changed.', [
          'feed' => $feed,
          'item' => $item,
          'entity' => $translation_entity,
          'entity_label' => $this->identifyEntity($translation_entity, $feed),
        ]);
        return;
      }
      if (!$translation_entity && $existing_entity && !$changed) {
        $state->report(StateType::SKIP, 'Skipped because the source data has not changed.', [
          'feed' => $feed,
          'item' => $item,
          'entity' => $existing_entity,
          'entity_label' => $this->identifyEntity($existing_entity, $feed),
        ]);
        return;
      }
    }

    $working_entity = $existing_entity && $existing_entity->hasTranslation($language_code)
      ? $existing_entity->getTranslation($language_code)
      : $existing_entity;

    // Build a new entity.
    if (!$existing_entity && !$skip_new) {
      $working_entity = $this->newEntity($feed);
    }

    if ($existing_entity && $existing_entity->language()->getId() !== $language_code && !$translation_entity) {
      $translation_entity = $existing_entity->addTranslation($language_code);
      $source_langcode = $existing_entity->language()->getId();
      $class = $translation_entity->getEntityType()->get('content_translation_metadata');
      $handler = $this->entityTypeManager->getHandler($translation_entity->getEntityTypeId(), 'translation');
      $metadata = new $class($translation_entity, $handler);
      $traits = class_uses($translation_entity);
      if (in_array('Drupal\Core\Entity\EntityOwnerInterface', $traits)) {
        $translation_entity->setOwner($translation_entity->getOwner());
      }
      if (in_array('Drupal\Core\Entity\  use EntityPublishedTrait', $traits)) {
        $metadata->setCreatedTime($translation_entity->getCreatedTime());
      }
      $metadata->setSource($source_langcode);
    }

    $item_data = $item->toArray();

    $working_entity = $translation_entity ?: $working_entity;

    if (empty($working_entity)) {
      $this->messenger()->addError(t('Unable to create or update entity for feed @feed.', ['@feed' => $feed->label()]));
      return;
    }

    // Set feeds_item values.
    $feeds_item = $working_entity->get('feeds_item')->getItemByFeed($feed, TRUE);
    $feeds_item->hash = $hash;

    // Set new revision if needed.
    if ($this->configuration['revision']) {
      $working_entity->setNewRevision(TRUE);
      $working_entity->setRevisionCreationTime($this->dateTime->getRequestTime());
    }

    if (empty($item_data)) {
      // If there is no data to map, we can skip the rest of the process.
      $state->report(StateType::SKIP, 'Skipped because there is no data to map.', [
        'feed' => $feed,
        'item' => $item,
        'entity' => $working_entity,
        'entity_label' => $this->identifyEntity($working_entity, $feed),
      ]);
      return;
    }

    // Create a DynamicItem from the item data.
    $map_item = new DynamicItem();
    $map_item->fromArray($item_data);

    // Set field values.
    $this->map($feed, $working_entity, $map_item);

    // Validate the entity.
    $feed->dispatchEntityEvent(FeedsEvents::PROCESS_ENTITY_PREVALIDATE, $working_entity, $item);
    // Skip validation entirely if the "skip_validation" setting is enabled
    // and there are no types specified.
    $skip_validation = !empty($this->configuration['skip_validation']) && empty($this->configuration['skip_validation_types']);
    if (!$skip_validation) {
      $this->entityValidate($working_entity, $feed);
    }

    // Dispatch presave event.
    $feed->dispatchEntityEvent(FeedsEvents::PROCESS_ENTITY_PRESAVE, $working_entity, $item);

    // This will throw an exception on failure.
    $this->entitySaveAccess($working_entity);
    // Set imported time.
    $feeds_item->imported = $this->dateTime->getRequestTime();

    // And... Save! We made it.
    $this->storageController->save($working_entity);

    // Dispatch postsave event.
    $feed->dispatchEntityEvent(FeedsEvents::PROCESS_ENTITY_POSTSAVE, $working_entity, $item);

    // Track progress.
    $operation = $existing_entity && ($existing_entity->language()->getId() === $language_code || ($existing_entity->language()->getId() !== $language_code && $existing_entity->hasTranslation($language_code)))
      ? StateType::UPDATE
      : StateType::CREATE;
    $state->report($operation, '', [
      'feed' => $feed,
      'item' => $item,
      'entity' => $working_entity,
      'entity_label' => $this->identifyEntity($working_entity, $feed),
    ]);
  }

  /**
   * Find existing entity by translation key.
   *
   * @param string $target_field
   *   The target field to search by.
   * @param mixed $entity_id
   *   The value of the translation key to search for.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The existing entity if found, NULL otherwise.
   *
   */
  protected function findExistingEntity($target_field, $target_value) {
    $storage = $this->entityTypeManager->getStorage($this->entityType());

    // Check if entity type supports translation key field
    if (!$this->hasKeyField($this->entityType(), $target_field)) {
      return NULL;
    }

    // Build query to find existing entity by translation key
    $query = $storage->getQuery();
    $query->condition($target_field, $target_value);
    $query->accessCheck(TRUE);

    $entity_type = $this->entityTypeManager->getDefinition($this->entityType());
    // Add bundle condition if entity type has bundles
    if ($entity_type->hasKey('bundle')) {
      $query->condition($entity_type->getKey('bundle'), $this->bundle());
    }

    $entity_ids = $query->execute();

    if (!empty($entity_ids)) {
      $entity_id = reset($entity_ids);
      return $storage->load($entity_id);
    }

    return NULL;
  }

  /**
   * Create new entity with translation key.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to add translation to.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item containing field data.
   * @param string $language_code
   *   The language code for the translation.
   */
  protected function updateEntity(FeedInterface $feed, EntityInterface $entity, ItemInterface $item, $language_code) {
    $this->map($feed, $entity, $item);
    $entity->save();
  }

  /**
   * Check if language code is valid.
   */
  protected function isValidLanguageCode($language_code) {
    $language_manager = \Drupal::languageManager();
    $languages = $language_manager->getLanguages();
    return isset($languages[$language_code]);
  }

  /**
   * Check if entity type has a specific key field.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The field name to check.
   *
   * @return bool
   *   TRUE if the entity type has the specified key field, FALSE otherwise.
   */
  protected function hasKeyField($entity_type, $field_name) {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $this->bundle());
    return isset($field_definitions[$field_name]);
  }

  /**
   * {@inheritdoc}
   */
  protected function map(FeedInterface $feed, EntityInterface $entity, ItemInterface $item) {
    $mappings = $this->feedType->getMappings();
    $map_mappings = $feed->getType()->getMappings();
    if ($entity instanceof TranslatableInterface && !$entity->isDefaultTranslation()) {
      foreach ($map_mappings as $idx => $mapping) {
        if (empty($mapping['target']) || (empty($mapping['map']['value']) && empty($mapping['map']['target_id']))) {
          continue;
        }

        // If the mapping target is not translatable, remove it from the item.
        if ($mapping['target'] !== 'langcode' && $mapping['target'] !== 'translation_key' && !$entity->getFieldDefinitions()[$mapping['target']]->isTranslatable()) {
          unset($map_mappings[$idx]);
          $this->messenger()->addWarning(t('The mapping target @target is not translatable and will be removed from the item.', ['@target' => $mapping['target']]));
        }
      }
    }

    // Mappers add to existing fields rather than replacing them. Hence we need
    // to clear target elements of each item before mapping in case we are
    // mapping on a prepopulated item such as an existing node.
    foreach ($map_mappings as $delta => $mapping) {
      if ($mapping['target'] == 'feeds_item') {
        // Skip feeds item as this field gets default values before mapping.
        continue;
      }
      // Clear the target.
      $this->clearTarget($feed, $entity, $this->feedType->getTargetPlugin($delta), $mapping['target']);
    }

    // Gather all of the values for this item.
    $source_values = [];
    foreach ($map_mappings as $delta => $mapping) {
      $target = $this->feedType->getTargetPlugin($delta);

      foreach ($mapping['map'] as $column => $source) {

        if ($source === '') {
          // Skip empty sources.
          continue;
        }

        if (!isset($source_values[$delta][$column])) {
          $source_values[$delta][$column] = [];
        }

        $value = $item->get($source);
        if (!is_array($value)) {
          $source_values[$delta][$column][] = $value;
        }
        else {
          $source_values[$delta][$column] = array_merge($source_values[$delta][$column], $value);
        }
      }
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
    foreach ($map_mappings as $delta => $mapping) {
      $plugin = $this->feedType->getTargetPlugin($delta);
      $target = $mapping['target'];
      if ($entity->hasField($target) && $target !== 'langcode') {
        $entity->set($target, NULL);
      }
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

}
