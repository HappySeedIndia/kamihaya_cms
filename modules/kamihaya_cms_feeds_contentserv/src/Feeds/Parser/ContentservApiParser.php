<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Parser;

use Drupal\content_moderation\ModerationInformation;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\File\FileSystemInterface;
use GuzzleHttp\Psr7\Stream;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Feeds\Parser\ParserBase;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\StateInterface;
use Drupal\feeds\StateType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\kamihaya_cms_feeds_contentserv\Service\ContentservClient;
use Drupal\kamihaya_cms_feeds_contentserv\Trait\ContentservApiTrait;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a Contentserv API feed parser.
 *
 * @FeedsParser(
 *   id = "kamihaya_contentserv",
 *   title = "Kamihaya Contentserv",
 *   description = @Translation("Parse Contentserv JSON gotten by Kamihaya Contentserv API."),
 * )
 */
class ContentservApiParser extends ParserBase implements ContainerFactoryPluginInterface {

  use ContentservApiTrait;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\kamihaya_cms_feeds_contentserv\Service\ContentservClient $contentservClient
   *   The Contentserv client service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\content_moderation\ModerationInformation $moderationInformation
   *   The content moderation information service.
   */
  public function __construct
    (array $configuration,
    $plugin_id,
    array $plugin_definition,
    protected ContentservClient $contentservClient,
    protected FileSystemInterface $fileSystem,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ModerationInformation $moderationInformation) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('kamihaya_cms_feeds_contentserv.contentserv_client'),
      $container->get('file_system'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('kamihaya_cms_feeds_contentserv'),
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    // Get sources.
    $sources = [];
    $skip_sources = [];
    foreach ($feed->getType()->getMappingSources() as $key => $info) {
      if (isset($info['type']) && $info['type'] !== 'kamihaya_json') {
        $skip_sources[$key] = $key;
        continue;
      }
      if (!empty($info['value']) && trim(strval($info['value'])) !== '') {
        $sources[$key] = $info['value'];
      }
    }

    // Get the fetcher configuration.
    $fetcher_config = $feed->getType()->getFetcher()->getConfiguration();
    // Get the processor configuration.
    $processor_config = $feed->getType()->getProcessor()->getConfiguration();
    // Get the feed fetcher configuration.
    $feed_config = $feed->getConfigurationFor($feed->getType()->getFetcher());

    // Get the additional language code.
    $langcode = !empty($processor_config['addtional_langcode']) ? $processor_config['addtional_langcode'] : '';
    $data_id = '';

    /** @var \Drupal\kamihaya_cms_feeds_contentserv\Result\ContentservApiFetcherResultInterface $fetcher_result */
    if (empty($fetcher_result->getResults())) {
      // If there are no results, set the last imported time and throw an exception.
      $this->updateLastImportedTime($feed);
      throw new EmptyFeedException(strtr('@name: There is no fetched data.', ['@name' => $feed->label()]));
    }
    $result = new ParserResult();
    $mappings = $feed->getType()->getMappings();

    $data_type = $fetcher_config['data_type'];
    $url = $fetcher_config['json_api_url'];
    $data_url = "/core/v1/" . strtolower($data_type) . '/';

    if (empty($state->pointer)) {
      $state->pointer = 0;
    }

    // Unpublish entities that are not in the fetched results.
    $this->unPublishEntities($fetcher_result->getResults(), $feed);

    // Get the last imported time to fetch only the changed data.
    $last_imported_time = 0;
    if (!empty($feed_config['last_import_start_time'])) {
      $last_imported_time = $feed_config['last_import_start_time'];
    }
    if (empty($last_imported_time) && !empty($feed->getImportedTime())) {
      $last_imported_time = $feed->getImportedTime();
    }

    $results = array_slice($fetcher_result->getResults(), $state->pointer, $this->configuration['data_limit']);
    $skipped_count = 0;

    foreach ($results as $result_data) {
      try {
        // Skip the data if its last changed time is less than the last imported time.
        if (!$fetcher_config['filter_by_date'] && !empty($result_data['Changed']) && strtotime($result_data['Changed']) < $last_imported_time) {
          $state->report(StateType::SKIP, strtr('Skipped the data because it is not changed since last import. [@type ID: @id]', [
            '@type' => $data_type,
            '@id' => $result_data['ID'],
          ]), [
            'feed' => $feed,
          ]);
          $skipped_count++;
          $state->pointer++;
          continue;
        }
        // Get the detail of product.
        $data_id = $result_data['ID'];
        $options = [];
        if ($this->hasTagsInSource($feed)) {
          // Set the expand query if the feed has 'Tags' in source.
          $options[RequestOptions::QUERY] = ['expand' => 'Tags'];
        }
        $response = $this->getData($feed, $url, "$data_url{$data_id}", $fetcher_result->getAccessToken(), $options);
        $data = json_decode($response, TRUE);

        if (empty($data[$data_type])) {
          $skipped_count++;
          $state->pointer++;
          continue;
        }

        $item = new DynamicItem();
        // Set the sources to the item.
        foreach ($sources as $key => $json_key) {
          if (isset($skip_sources[$key])) {
            continue;
          }
          // Get the value from the result data.
          $value = $this->getAttributeValue($data[$data_type], $json_key);
          // Check if the target is media.
          if (!empty($value) && $target = $this->getMediaTarget($feed, $key)) {
            // Get the label of the file.
            $label = $this->getAttributeValue($data[$data_type], 'Label');
            // Check the file extension.
            if (!$this->checkFileExtention($target, $label)) {
              // Skip the file if the file extension is not correct.
              $state->report(StateType::SKIP, strtr('Skipped the file because the file extentioon is not correct. [@type ID: @id, File label: @label, File ID: @value]', [
                '@type' => $data_type,
                '@id' => $data_id,
                '@label' => $label,
                '@value' => $value,
              ]), [
                'feed' => $feed,
                'item' => $item,
              ]);
              $state->setMessage(strtr('@name - Skipped the file because the file extentioon is not correct. [@type ID: @id, File label: @label, File ID: @value]', [
                '@name' => $feed->label(),
                '@type' => $data_type,
                '@id' => $data_id,
                '@label' => $label,
                '@value' => $value,
              ]), 'warning');
              continue;
            }

            try {
              // Create the media file.
               $value = $this->createMediaFile($feed, $fetcher_result, $target, $value, $label);
            } catch (GuzzleException $e) {
              // Skip the file if failed to create media.
              $state->report(StateType::FAIL, strtr('Skipped the file because failed to create the media file. [@type ID: @id, File label: @label, File ID: @value]', [
                '@type' => $data_type,
                '@id' => $data_id,
                '@label' => $label,
                '@value' => $value,
              ]), [
                'feed' => $feed,
                'item' => $item,
              ]);
              $state->setMessage(strtr('@name - Skipped the file because failed to create the media file. [@type ID: @id, File label: @label, File ID: @value, error: @error]', [
                '@name' => $feed->label(),
                '@type' => $data_type,
                '@id' => $data_id,
                '@label' => $label,
                '@value' => $value,
                '@error' => $e->getMessage(),
              ]), 'error');
              continue;
            }
          }
          $item->set($key, $value);
        }

        if (!empty($langcode)) {
          // Get additional language data.
          $add_options = $options;
          //  Add the language code to the query.
          $add_options[RequestOptions::QUERY] = ['lang' => $langcode];
          // Get the additional data.
          $response = $this->getData($feed, $url, "$data_url{$result_data['ID']}", $fetcher_result->getAccessToken(), $add_options);
          $data = json_decode($response, TRUE);
          if (!empty($data[$data_type])) {
            $add_item = new DynamicItem();
            // Set the sources to the item.
            foreach ($sources as $key => $json_key) {
              if (isset($skip_sources[$key])) {
                continue;
              }
              // Get the value from the result data.
              $value = $this->getAttributeValue($data[$data_type], $json_key);

              $alt = FALSE;
              // Skip the value is not set or the value is same as the original language value and not alt or description.
              foreach ($mappings as $mapping) {
                if (empty($mapping['map']['alt']) || $mapping['map']['alt'] !== $key) {
                  continue;
                }
                $alt = TRUE;
              }
              // Skip the value is not set or the value is same as the original language value and not alt or description.
              if (strlen($value) === 0 || ($value === $item->get($key) && !$alt)) {
                continue;
              }
              // Check if the target is media.
              if (!empty($value) && $target = $this->getMediaTarget($feed, $key)) {
                // Get the label of the file.
                $label = $this->getAttributeValue($data[$data_type], 'Label');
                // Check the file extension.
                if (!$this->checkFileExtention($target, $label)) {
                  continue;
                }
                try {
                  // Create the media file.
                  $value = $this->createMediaFile($feed, $fetcher_result, $target, $value, $label);
                } catch (GuzzleException $e) {
                  // Skip the file if failed to create media.
                  $state->report(StateType::SKIP, strtr('Skipped the file because failed to create the media file. [@type ID: @id, File label: @label, File ID: @value, Language: @lang]', [
                    '@type' => $data_type,
                    '@id' => $data_id,
                    '@label' => $label,
                    '@value' => $value,
                    '@lang' => $langcode,
                  ]), [
                    'feed' => $feed,
                    'item' => $item,
                  ]);
                  $state->setMessage(strtr('@name - Skipped the file because failed to create the media file. [@type ID: @id, File label: @label, File ID: @value, Language: @lang, error: %error]', [
                    '@name' => $feed->label(),
                    '@type' => $data_type,
                    '@id' => $data_id,
                    '@label' => $label,
                    '@value' => $value,
                    '@lang' => $langcode,
                    '%error' => $e->getMessage(),
                  ]), 'error');
                  continue;
                }
              }
              $add_item->set($key, $value);
            }
            // Add the additional language data to the item.
            if (count($add_item->toArray())) {
              $add_item->set('translation', TRUE);
              $item->set($langcode, $add_item);
            }
          }
        }

        $item->set('access_token', $fetcher_result->getAccessToken());
        $result->addItem($item);
        $state->pointer++;
      }
      catch (GuzzleException $e) {
        $args = [
          '@name' => $feed->label(),
          '%error' => $e->getMessage(),
          '@type' => $data_type,
          '@id' => $data_id,
        ];
        $state->report(StateType::FAIL, strtr('@name - The error occurs while getting data because of error "%error" [@type ID: @id].', $args), [
          'feed' => $feed,
          'item' => $item,
        ]);
        $this->logger->error('Failed to get data for feed @name: @error', [
          '@name' => $feed->label(),
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // Report progress.
    $state->total = count($fetcher_result->getResults());

    $state->progress($state->total, $state->pointer);
    if ($state->pointer >= $state->total) {
      // Update the last imported time.
      $this->updateLastImportedTime($feed);
    }

    // Set progress to complete if no more results are parsed.
    if (!$result->count() && !$skipped_count) {
      $state->setCompleted();
      // If no results are parsed, update the last imported time.
      $this->updateLastImportedTime($feed);
    }

    return $result;
  }

  /**
   * Get the attribute value from JSON.
   *
   * @param array $json
   *   The JSON data.
   * @param string $render_value
   *   The render value.
   *
   * @return string
   *   The attribute value.
   */
  protected function getAttributeValue(array $json, $render_value) {
    if ($render_value === 'FULL') {
      return $json;
    }
    $render_value = str_replace(' ', '', $render_value);
    $attributes = explode(':', $render_value);
    $value = $json;
    foreach ($attributes as $attribute) {
      if (!is_array($value)) {
        return '';
      }
      $attribute = trim($attribute);
      if (strpos($attribute, 'EXCLUDE') === 0) {
        $attribute = str_replace(['EXCLUDE(', ')'], '', $attribute);
        if (empty($value[$attribute])) {
          continue;
        }
        unset($value[$attribute]);
        continue;
      }
      if (isset($value[$attribute])) {
        $value = $value[$attribute];
        continue;
      }
      if (strpos($attribute, '|') !== FALSE) {
        $attrs = explode('|', $attribute);
        if (!empty($attrs[0]) && strpos($attrs[0], '=') !== FALSE) {
          $key = explode('=', $attrs[0])[0];
          $val = explode('=', $attrs[0])[1];
          $val_key = $attrs[1];
          foreach ($value as $item) {
            if (isset($item[$key]) && $item[$key] === $val && isset($item[$val_key])) {
              return $item[$val_key];
            }
          }
          return '';
        }
      }
    }
    if (is_array($value) && empty($value)) {
      return '';
    }
    if (is_array($value)) {
      return json_encode($value);
    }
    return $value;
  }

  /**
   * Check the source is media or not.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   * @param string $source
   *   The source.
   *
   * @return \Drupal\feeds\FieldTargetDefinition|null
   *   The media target.
   */
  protected function getMediaTarget(FeedInterface $feed, $source) {
    $mappings = $feed->getType()->getMappings();
    $targets = $feed->getType()->getMappingTargets();
    foreach ($mappings as $value) {
      if (empty($value['target']) || empty($value['map']['target_id']) || $value['map']['target_id'] !== $source || empty($targets[$value['target']])) {
        continue;
      }
      /** @var \Drupal\feeds\FieldTargetDefinition $target */
      $target = $targets[$value['target']];
      if ($target->getFieldDefinition()->getType() === 'file' || $target->getFieldDefinition()->getType() === 'image') {
        return $target;
      }
    }
    return NULL;
  }

  /**
   * Check the file extention.
   *
   * @param \Drupal\feeds\FieldTargetDefinition $target
   *   The target.
   * @param string $uri
   *   The uri.
   *
   * @return bool
   *   Whether the file extention is correct.
   */
  protected function checkFileExtention(FieldTargetDefinition $target, $uri) {
    $extensions = $target->getFieldDefinition()->getSetting('file_extensions');
    $regex = '/\.(' . preg_replace('/ +/', '|', preg_quote($extensions)) . ')$/i';
    if (!preg_match($regex, $uri)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Create media file.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed.
   * @param \Drupal\feeds\Result\FetcherResultInterface $fetcher_result
   *   The fetcher result.
   * @param \Drupal\feeds\FieldTargetDefinition $target
   *   The target.
   * @param string $file_id
   *   The file id.
   * @param string $file_name
   *   The file name.
   * @param string $langcode
   *   The langcode.
   *
   * @return int
   *   The file id.
   */
  protected function createMediaFile(FeedInterface $feed, FetcherResultInterface $fetcher_result, FieldTargetDefinition $target, $file_id, $file_name, $langcode = '', $retry = FALSE) {
    // Get the fetcher and processor configuration.
    $fetcher_config = $feed->getType()->getFetcher()->getConfiguration();
    $processor_config = $feed->getType()->getProcessor()->getConfiguration();
    // Get the file destination.
    $field_name = $target->getFieldDefinition()->getName();
    $field_storage = FieldStorageConfig::loadByName($target->getFieldDefinition()->getTargetEntityTypeId(), $field_name);
    $direcotry = $target->getFieldDefinition()->getSetting('file_directory');
    $destination = $field_storage->getSetting('uri_scheme');
    $file_destination = "{$destination}://{$direcotry}";
    // Prepare the directory.
    $this->fileSystem->prepareDirectory($file_destination, FileSystemInterface::CREATE_DIRECTORY);
    $file_destination = "{$file_destination}/{$file_name}";
    // Create the file resource.
    $resource = fopen($file_destination, 'w');
    $url = $fetcher_config['json_api_url'];
    $data_url = "/core/v1/file/downloadurl/$file_id";
    // Get the download url.
    $download_url = $this->getData($feed, $url, $data_url, $fetcher_result->getAccessToken());

    if (empty($download_url)) {
      return NULL;
    }
    // Remove the escape characters.
    $download_url = str_replace(["\\", '"'], '', $download_url);
    $options = [
      RequestOptions::TIMEOUT => $fetcher_config['request_timeout'],
      RequestOptions::SINK => $resource,
      RequestOptions::HEADERS => [
        'Authorization' => "Bearer {$fetcher_result->getAccessToken()}",
      ],
      RequestOptions::HTTP_ERRORS => FALSE,
    ];

    if (!empty($langcode)) {
      $options[RequestOptions::QUERY] = ['lang' => $langcode];
    }

    // Get the file.
    $response = $this->contentservClient->request($feed, $download_url, $options);
    $status_code = $response->getStatusCode();
    if ($status_code == 401 || $status_code == 403) {
      // Get the access token if the status code is 401 or 403.
      $token = $this->getAccessToken($feed, $url);
      $feed_config = $feed->getConfigurationFor($feed->getType()->getFetcher());
      if (!empty($feed_config['access_token'])) {
        $feed_config['access_token'] = $token;
        $feed->setConfigurationFor($feed->getType()->getFetcher(), $feed_config);
      }
      // Set the new access token to the header.
      $options[RequestOptions::HEADERS]['Authorization'] = "Bearer $token";
      // Get the data with the new access token.
      $response = $this->contentservClient->request($feed, $download_url, $options);
      $status_code = $response->getStatusCode();
    }
    if ($status_code == 429 && !$retry) {
      // Retry if the status code is 429.
      $sleep_seconds = !empty($response->getHeader('Retry-After')[0]) ? $response->getHeader('Retry-After')[0] : 30;
      // Sleep the seconds which is set in the header as Retry-After.
      sleep($sleep_seconds);
      // Retry to get the file.
      return $this->createMediaFile($feed, $fetcher_result, $target, $file_id, $file_name, $langcode, TRUE);
    }

    if ($status_code != 200) {
      // Throw an exception if the status code is not 200.
      $args = ['%status' => $status_code];
      throw new GuzzleException(strtr('Faild to get the file with status code "%status".', $args));
    }

    /** @var \GuzzleHttp\Psr7\Stream $stream */
    $stream = $response->getBody();
    if (empty($stream) || !($stream instanceof Stream) || !is_readable($file_destination)) {
      // Throw an exception if the stream is not correct.
      throw new GuzzleException('Faild to get the file because the stream is not correct.');
    }
    // Save the file.
    $files = $this->entityTypeManager->getStorage('file')->loadByProperties(['uri' => $file_destination]);
    if (!empty($files)) {
      /** @var \Drupal\file\FileInterface $file */
      $file = reset($files);
      $file->setChangedTime(time());
    }
    else {
      // Create the file entity.
      $entity_values = [
        'uri' => $file_destination,
        'status' => FileInterface::STATUS_PERMANENT,
      ];
      if (!empty($langcode)) {
        $entity_values['langcode'] = $langcode;
      }
      if ($processor_config['owner_feed_author']) {
        $entity_values['uid'] = $feed->getOwnerId();
      }
      else {
        $entity_values['uid'] = $processor_config['owner_id'];
      }
      $file = File::create($entity_values);
    }
    $file->save();
    return $file->id();
  }

  /**
   * Unpublish entities that are not in the fetched results.
   *
   * @param array $results
   *   The fetched results.
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed object.
   */
  protected function unPublishEntities(array $results, FeedInterface $feed) {
    $fetcher_config = $feed->getType()->getFetcher()->getConfiguration();
    // If unpublish content is not enabled, return early.
    if (empty($fetcher_config['unpublish_content'])) {
      return;
    }
    // Re-create the results array to contain only the IDs.
    $result_ids = array_map(function ($item) {
      return $item['ID'];
    }, $results);

    // Get the unique key from the feed type mappings.
    $unique_key = NULL;
    foreach ($this->feedType->getMappings() as $mapping) {
      if (!empty($mapping['unique'])) {
        $unique_key = $mapping['target'];
        break;
      }
    }
    if (empty($unique_key)) {
      return;
    }
    // Get the processor.
    $processor = $feed->getType()->getProcessor();
    // Get the entity type from the feed type.
    $entity_type_id = $processor->entityType();
    // The entity type.
    $etntity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    // Get the bundle key for the entity type.
    $bundle_key = $etntity_type->getKey('bundle');
    // Get published key for the entity type.
    $published_key = $etntity_type->getKey('published');
    // Get the entity IDs having the unique key set.
    $entity_ids = $this->entityTypeManager->getStorage($entity_type_id)->getQuery()
      ->condition($bundle_key, $processor->bundle())
      ->condition($unique_key, NULL, 'IS NOT NULL')
      ->condition($published_key, 1)
      ->accessCheck(FALSE)
      ->execute();
    if (empty($entity_ids)) {
      return;
    }

    // Load the entities and check if they are in the results.
    $entities = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple($entity_ids);
    foreach ($entities as $entity) {
      if (!($entity->get($unique_key)->value) || in_array($entity->get($unique_key)->value, $result_ids)) {
        // If the entity is in the results, skip it.
        continue;
      }
      $this->unPublishEntity($entity);
      // If the entity is not translatable, skip it.
      if ($entity instanceof TranslatableInterface) {
        $translations = $entity->getTranslationLanguages();
        // Unpublish translations if they exist.
        if (empty($translations)) {
          continue;
        }
        foreach ($translations as $langcode => $translation) {
          // Load the translation entity.
          $translation_entity = $entity->getTranslation($langcode);
          // Unpublish the translation entity.
          $this->unPublishEntity($translation_entity);
        }
      }
      $this->logger->info('Unpublished entity @id of type @type for feed @feed', [
        '@id' => $entity->id(),
        '@type' => $entity_type_id,
        '@feed' => $feed->label(),
      ]);
    }
  }

  /**
   * Get the unpublished moderation status for the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   *
   * @return string|null
   *   The unpublished moderation state ID or NULL if not found.
   */
  protected function getUnpublishedModerationState(ContentEntityInterface $entity) {
    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    if (!$workflow) {
      return NULL;
    }
    // Get the moderation states for the workflow.
    $states = $workflow->getTypePlugin()->getStates();
    foreach ($states as $state_id => $state) {
      if (!$state->isPublishedState() && $state->isDefaultRevisionState()) {
        // Return the moderation state if it is unpublished and is the default revision.
        return $state_id;
      }
    }
    // If no unpublished state is found, return NULL.
    return NULL;
  }

  /**
   * Unpublish the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to unpublish.
   */
  protected function unPublishEntity(ContentEntityInterface $entity) {
    // If the entity's unique key is not in the results, unpublish it.
    if ($state = $this->getUnpublishedModerationState($entity)) {
      $entity->set('moderation_state', $state);
    }
    $entity->setUnpublished();
    $entity->save();
  }
  /**
   * Update the last imported time in the feed configuration.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed object.
   */
  protected function updateLastImportedTime(FeedInterface $feed) {
    $fetcher_config = $feed->getType()->getFetcher()->getConfiguration();
    if ($fetcher_config['filter_by_date']) {
      return;
    }
    // Update the last imported time in the feed configuration.
    $feed_config = $feed->getConfigurationFor($feed->getType()->getFetcher());
    $feed_config['last_import_start_time'] = time();
    $feed->setConfigurationFor($feed->getType()->getFetcher(), $feed_config);
    // Save the feed.
    $feed->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedCustomSourcePlugins(): array {
    return ['kamihaya_json'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'data_limit' => 50,
    ];
  }

}
