<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Plugin\Tamper;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\Entity\Feed;
use Drupal\feeds\FeedInterface;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation for importing with the additional feeds.
 *
 * @Tamper(
 *   id = "additional_feeds_import",
 *   label = @Translation("Additional Feeds Import"),
 *   description = @Translation("Import with the additional feeds."),
 *   category = "Other",
 *   handle_multiples = TRUE
 * )
 */
class AdditionalFeedsImport extends TamperBase implements ContainerFactoryPluginInterface, KamihayaTamperInterface {

  const SETTING_FEEDS = 'feeds';
  const SETTING_SKIP_TRANSLATED_ITEM = 'skip_translated_item';

  /**
   * Constructs a AdditionalFeedsImport plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param mixed $source_definition
   *   A definition of which sources there are that Tamper plugins can use.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $source_definition, protected EntityTypeBundleInfoInterface $entityTypeBundleInfo, protected EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $source_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $configuration['source_definition'],
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_FEEDS] = '';
    $config[self::SETTING_SKIP_TRANSLATED_ITEM] = TRUE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo('feeds_feed');
    $option = [];
    foreach ($bundles as $bundle_name => $bundle_info) {
      $option[$bundle_name] = $bundle_info['label'];
    }
    $form[self::SETTING_FEEDS] = [
      '#type' => 'select',
      '#title' => $this->t('Additional feeds'),
      '#options' => $option,
      '#default_value' => $this->getSetting(self::SETTING_FEEDS),
    ];

    $form[self::SETTING_SKIP_TRANSLATED_ITEM] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip translated item'),
      '#default_value' => $this->getSetting(self::SETTING_SKIP_TRANSLATED_ITEM),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_FEEDS => $form_state->getValue(self::SETTING_FEEDS),
      self::SETTING_SKIP_TRANSLATED_ITEM => $form_state->getValue(self::SETTING_SKIP_TRANSLATED_ITEM),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function postParseTamper(FeedInterface $feed, $data, TamperableItemInterface $item) {
    return $data;
    if (is_null($data) || (is_array($data) && empty($data)) || (!is_array($data) && strlen($data) === 0)) {
      return;
    }
    if (!empty($item->getSource()['skipped'])) {
      // Skip the item if it has been marked as skipped.
      return;
    }
    if ($this->getSetting(self::SETTING_SKIP_TRANSLATED_ITEM) && !empty($item->getSource()['translation'])) {
      return;
    }

    $name = $feed->label() . ' - ' . $this->getSetting(self::SETTING_FEEDS);
    $feeds = $this->entityTypeManager->getStorage('feeds_feed')->loadByProperties(['title' => $name]);
    if (!empty($feeds)) {
      $feed = reset($feeds);
    }
    else {
      $values = [
        'title' => $name,
        'type' => $this->getSetting(self::SETTING_FEEDS),
        'feeds_log' => FALSE,
      ];
      $feed = Feed::create($values);
    }

    if ($feed->bundle() !== 'pxc_dam_image_import') {
      return;
    }

    if ($feed->hasField('field_json_data') && is_array($data)) {
      // Save the JSON data to the feed.
      $feed->set('field_json_data', json_encode($data));
    }
    else {
      // Save the data as a source to the feed.
      $feed->setSource($data);
    }
    if (!empty($item->getSource()['access_token'])) {
      // Set the access token to the feed configuration.
      $feed_config = $feed->getConfigurationFor($feed->getType()->getFetcher());
      $feed_config['access_token'] = $item->getSource()['access_token'];
      $feed->setConfigurationFor($feed->getType()->getFetcher(), $feed_config);
    }
    $feed->save();
    try {
      // Execute the import.
      $feed->import();
    }
    catch (\Exception $e) {
      throw $e;
    }
    finally {
      // Unlock the feed if it was locked.
      if ($feed->isLocked()) {
        $feed->unlock();
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveTamper(FeedInterface $feed, EntityInterface $entity, ?TamperableItemInterface $item, $source) {
    return;
  }

}
