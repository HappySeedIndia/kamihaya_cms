<?php

namespace Drupal\kamihaya_cms_google_map_paragraph\Form\Config;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the configuration form.
 */
class GoogleMapSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kamihaya_cms_google_map_paragraph_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['kamihaya_cms_google_map_paragraph.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $site_config = $this->config('kamihaya_cms_google_map_paragraph.settings');

    $form['google_map'] = [
      '#type' => 'details',
      '#title' => $this->t('Google Map設定'),
      '#open' => TRUE,
    ];
    $form['google_map']['google_map_api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('API URL'),
      '#default_value' => $site_config->get('google_map_api_url'),
      '#required' => TRUE,
      '#description' => $this->t('最後に/(スラッシュ)を付けないでください。'),
    ];
    $form['google_map']['google_map_api_kay'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $site_config->get('google_map_api_kay'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->config('kamihaya_cms_google_map_paragraph.settings');
    $settings->set('google_map_api_url', $form_state->getValue('google_map_api_url'));
    $settings->set('google_map_api_kay', $form_state->getValue('google_map_api_kay'));
    $settings->save();

    parent::submitForm($form, $form_state);
  }

}
