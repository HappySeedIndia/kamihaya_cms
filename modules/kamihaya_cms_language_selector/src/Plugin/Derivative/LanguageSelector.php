<?php

namespace Drupal\kamihaya_cms_language_selector\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides language selector block plugin definitions for all languages.
 */
class LanguageSelector extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs new LanguageSelector.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('language_manager')
    );
  }

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = [];

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if ($this->languageManager instanceof ConfigurableLanguageManagerInterface) {
      $info = $this->languageManager->getDefinedLanguageTypesInfo();
      $configurable_types = $this->languageManager->getLanguageTypes();
      foreach ($configurable_types as $type) {
        $this->derivatives[$type] = $base_plugin_definition;
        $this->derivatives[$type]['admin_label'] = $this->t('Dropdown Language (@type)', ['@type' => $info[$type]['name']]);
      }
      // If there is just one configurable type then change the title of the
      // block.
      if (count($configurable_types) == 1) {
        $this->derivatives[reset($configurable_types)]['admin_label'] = $this->t('Language Selector');
      }
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
