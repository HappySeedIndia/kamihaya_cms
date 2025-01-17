<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Plugin\Tamper;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\feeds\Entity\Feed;
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
 *   category = "Other"
 * )
 */
class AdditionalFeedsImport extends TamperBase implements ContainerFactoryPluginInterface {

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $source_definition, protected EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
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
      $container->get('entity_type.bundle.info')
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
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (is_null($data) || strlen($data) === 0) {
      return $data;
    }
    if ($this->getSetting(self::SETTING_SKIP_TRANSLATED_ITEM) && !empty($item->getSource()['translation'])) {
      return $data;
    }
    $values = [
      'title' => $this->t('Temporary feed for additional feeds import'),
      'type' => $this->getSetting(self::SETTING_FEEDS),
      'source' => $data,
      'feeds_log' => FALSE,
    ];

    $feed = Feed::create($values);
    if (!empty($item->getSource()['access_token'])) {
      $feed_config = $feed->getConfigurationFor($feed->getType()->getFetcher());
      $feed_config['access_token'] = $item->getSource()['access_token'];
      $feed->setConfigurationFor($feed->getType()->getFetcher(), $feed_config);
    }
    $feed->save();
    try {
      $feed->import();
    } catch (\Exception $e) {
      $feed->unlock();
      throw $e;
    } finally {
      $feed->delete();
    }
    return $data;
  }

}
