<?php

namespace Drupal\kamihaya_cms_google_map\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Help' block.
 */
#[Block(
  id: "google_map_block",
  admin_label: new TranslatableMarkup("Google Map"),
  forms: ['settings_tray' => FALSE]
)]
class GoogleMapBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The configuration object for the Google Map settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Creates a HelpBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $this->configFactory->get('kamihaya_cms_google_map.settings');
    $this->messenger = $this->messenger();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    // Check if the Google Map API key is configured.
    // If not, display a warning message.
    if (!$this->config->get('google_map_api_key')) {
      $this->messenger->addWarning($this->t('Google Map cannot be displayed. Please contact the site administrator.'));
      return $build;
    }

    $build['map_container'] = [
      '#type' => 'container',
      '#open' => TRUE,
    ];

    $build['map_container']['map'] = [
      '#markup' => "<div id='map'></div>",
      '#allowed_tags' => ['div'],
    ];

    // Attach the Google Map API library and settings.
    $build['#attached']['library'][] = 'kamihaya_cms_google_map/map';
    $build['#attached']['drupalSettings']['api_key'] = $this->config->get('google_map_api_key');
    $build['#attached']['drupalSettings']['default_lat'] = $this->config->get('google_map_center_latitude');
    $build['#attached']['drupalSettings']['default_lon'] = $this->config->get('google_map_center_longitude');
    $build['#attached']['drupalSettings']['default_zoom'] = intval($this->config->get('google_map_zoom_level'));

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
