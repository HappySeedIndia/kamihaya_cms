<?php

namespace Drupal\kamihaya_cms_deel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Kamihaya CMS Deel settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kamihaya_cms_deel_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['kamihaya_cms_deel.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Url'),
      '#default_value' => $this->config('kamihaya_cms_deel.settings')->get('api_url'),
    ];
    $form['token'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Token'),
      '#default_value' => $this->config('kamihaya_cms_deel.settings')->get('token'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('kamihaya_cms_deel.settings')
      ->set('api_url', $form_state->getValue('api_url'))
      ->set('token', $form_state->getValue('token'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
