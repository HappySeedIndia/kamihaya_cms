<?php

declare(strict_types=1);

namespace Drupal\kamihaya_cms_exabase_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure ExabaseApi settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'kamihaya_cms_exabase_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['kamihaya_cms_exabase_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Exabase API Endpoint'),
      '#default_value' => $this->config('kamihaya_cms_exabase_api.settings')->get('endpoint'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('kamihaya_cms_exabase_api.settings')
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
