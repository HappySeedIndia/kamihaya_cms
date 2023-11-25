<?php

namespace Drupal\kamihaya_cms_spiral_api\Form\Config;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Spiral Settings.
 */
class SpiralSettings extends ConfigFormBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Constructs a SpiralSettings object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager objects.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityFieldManagerInterface $entity_field_manager, TranslationInterface $string_translation) {
    parent::__construct($config_factory);
    $this->entityFieldManager = $entity_field_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kamihaya_cms_spiral_apiadmin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['kamihaya_cms_spiral_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('kamihaya_cms_spiral_api.settings');

    $form['spiral'] = [
      '#type' => 'details',
      '#title' => $this->t('SPIRAL'),
      '#open' => TRUE,
    ];

    $form['spiral']['spiral_api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Spiral Api Url'),
      '#default_value' => $config->get('spiral_api_url'),
    ];

    $form['spiral']['spiral_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spiral Api Key'),
      '#default_value' => $config->get('spiral_api_key'),
    ];

    $form['spiral']['spiral_application_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spiral Application ID'),
      '#default_value' => $config->get('spiral_application_id'),
    ];

    $form['spiral']['spiral_db_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spiral DB ID'),
      '#default_value' => $config->get('spiral_db_id'),
    ];

    $form['spiral']['spiral_site_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spiral Site ID'),
      '#default_value' => $config->get('spiral_site_id'),
    ];

    $form['spiral']['spiral_authentication_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spiral Authentication ID'),
      '#default_value' => $config->get('spiral_authentication_id'),
    ];

    $description = '<p>' . $this->t('Enter one value per line, in the format spiral_field_name|drupal_field_name.');
    $description .= '</p>';

    $lines = [];
    if (!empty($config->get('spiral_field_mapping'))) {
      foreach ($config->get('spiral_field_mapping') as $key => $value) {
        $lines[] = "$key|$value";
      }
    }

    $form['spiral']['spiral_field_mapping'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Field mapping'),
      '#description' => $description,
      '#default_value' => implode("\n", $lines),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('kamihaya_cms_spiral_api.settings');
    $form_state->cleanValues();

    foreach ($form_state->getValues() as $key => $value) {
      if (is_array($value)) {
        $config->set($key, $value);
        continue;
      }
      $config->set($key, Html::escape($value));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue('spiral_field_mapping');
    if (empty($value)) {
      return;
    }
    $mapping = $this->validateAndExtractMapping($form_state, $value);
    if (empty($mapping)) {
      return;
    }
    $form_state->setValue('spiral_field_mapping', $mapping);
  }

  /**
   * Extracts the values array.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $string
   *   The raw string to extract values from.
   *
   * @return array|null
   *   The array of extracted key/value pairs, or NULL if the string is invalid.
   */
  private function validateAndExtractMapping(FormStateInterface $form_state, $string) {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('user', 'user');
    $values = [];

    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    $errors = [];
    foreach ($list as $text) {
      // Check for an explicit key.
      $matches = [];
      if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $key = trim($matches[1]);
        $value = trim($matches[2]);
        if (empty($field_definitions[$value])) {
          $errors[] = "<li>$value</li>";
          continue;
        }
      }
      $values[$key] = $value;
    }

    if (!empty($errors)) {
      $message = $this->stringTranslation->formatPlural(count($errors),
        "The following field doesn't exist. <ul>@fields</ul>",
        "The following fields don't exist.<ul>@fields</ul>", [
          '@fields' => Markup::create($errors),
        ]);

      $form_state->setErrorByName('spiral_field_mapping', $message);
    }
    return $values;
  }

}
