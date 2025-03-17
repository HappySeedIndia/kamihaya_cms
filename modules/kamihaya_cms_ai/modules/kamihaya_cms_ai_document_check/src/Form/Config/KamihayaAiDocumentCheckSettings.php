<?php

namespace Drupal\kamihaya_cms_ai_document_check\Form\Config;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Kamihaya AI Document Check Settings.
 */
class KamihayaAiDocumentCheckSettings extends ConfigFormBase {

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface|null $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface|null $moduleHandler
   *   The module handler.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected $typedConfigManager = NULL,
    protected $moduleHandler = NULL,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('config.factory'),
    $container->get('config.typed'),
    $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kamihaya_ai_document_check_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['kamihaya_ai_document_check.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('kamihaya_ai_document_check.settings');
    // Design settings.
    $form['waiging_movie'] = [
      '#type' => 'details',
      '#title' => $this->t('Waiting Movie'),
      '#description' => $this->t('Upload a movie file for waiting .'),
      '#open' => TRUE,
    ];

    $steps = [
      'summarize' => $this->t('Summarize'),
      'copyright_check' => $this->t('Copyright check'),
      'company_check' => $this->t('Company rule check'),
    ];

    foreach ($steps as $key => $step) {
      $form['waiging_movie'][$key] = [
        '#type' => 'managed_file',
        '#title' => $step,
        '#upload_location' => 'public://document_chheck/waiting_movie/',
        '#default_value' => $config->get($key),
        '#upload_validators' => [
          'file_validate_extensions' => ['mp4'],
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('kamihaya_ai_document_check.settings');
    $form_state->cleanValues();

    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
