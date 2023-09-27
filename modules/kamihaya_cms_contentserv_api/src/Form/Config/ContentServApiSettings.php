<?php

namespace Drupal\kamihaya_cms_contentserv_api\Form\Config;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ContentServ API Settings.
 */
class ContentServApiSettings extends ConfigFormBase {

  /**
   * Undocumented variable.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new ContentServApiSettings.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeBundleInfo $entity_type_bundleInfo) {
    parent::__construct($config_factory);
    $this->entityTypeBundleInfo = $entity_type_bundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contentserv_api_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['kamihaya_cms_contentserv_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('kamihaya_cms_contentserv_api.settings');

    $form['contentserv'] = [
      '#type' => 'details',
      '#title' => $this->t('ContentServ'),
      '#open' => TRUE,
    ];

    $form['contentserv']['contentserv_api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('ContentServ Api Url'),
      '#default_value' => $config->get('contentserv_api_url'),
    ];

    $form['contentserv']['contentserv_api_folder_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ContentServ Folder ID'),
      '#default_value' => $config->get('contentserv_api_folder_id'),
    ];

    $form['contentserv']['contentserv_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ContentServ Username'),
      '#default_value' => $config->get('contentserv_username'),
    ];

    $form['contentserv']['contentserv_password'] = [
      '#type' => 'password',
      '#title' => $this->t('ContentServ Password'),
      '#default_value' => $config->get('contentserv_password'),
    ];

    $form['contentserv']['contentserv_api_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('ContentServ Api Interval'),
      '#description' => $this->t('Get data interval.'),
      '#default_value' => $config->get('contentserv_api_interval'),
      '#options' => [
        '60' => $this->t('1 minute'),
        '300' => $this->t('@count minutes', ['@count' => '5']),
        '600' => $this->t('@count minutes', ['@count' => '10']),
        '1800' => $this->t('@count minutes', ['@count' => '30']),
        '3600' => $this->t('1 hour'),
        '43200' => $this->t('@count hours', ['@count' => '12']),
        '86400' => $this->t('1 day'),
        '604800' => $this->t('1 week'),
      ],
    ];

    $form['contentserv']['contentserv_api_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('ContentServ Api Limit'),
      '#description' => $this->t('Data count getting at one api request.'),
      '#default_value' => $config->get('contentserv_api_limit'),
    ];

    $options = [];
    foreach ($this->entityTypeBundleInfo->getBundleInfo('commerce_product') ?: [] as $key => $option) {
      $options[$key] = $option['label'];
    }
    $form['contentserv']['contentserv_product_type'] = [
      '#type' => 'select',
      '#title' => $this->t('ContentServ Product Type(Drupal)'),
      '#default_value' => $config->get('contentserv_product_type'),
      '#options' => $options,
    ];

    $form['contentserv']['contentserv_api_last_executed_time'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Last Data Updated Time'),
    ];

    if (!empty($config->get('contentserv_api_last_executed_time'))) {
      $form['contentserv']['contentserv_api_last_executed_time']['#default_value']
        = DrupalDateTime::createFromTimestamp($config->get('contentserv_api_last_executed_time'));
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('kamihaya_cms_contentserv_api.settings');
    $form_state->cleanValues();

    foreach ($form_state->getValues() as $key => $value) {
      if ($key === 'contentserv_api_last_executed_time') {
        $date = new DrupalDateTime($value);
        $value = empty($value) ? 0 : $date->getTimestamp();
      }
      $config->set($key, Html::escape($value));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
