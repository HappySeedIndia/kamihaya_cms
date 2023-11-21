<?php

namespace Drupal\kamihaya_cms_spiral_api\Form\Config;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Spiral Settings.
 */
class SpiralSettings extends ConfigFormBase {

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

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('kamihaya_cms_spiral_api.settings');
    $form_state->cleanValues();

    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, Html::escape($value));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

}
