<?php

namespace Drupal\kamihaya_cms_ai_document_check\Form\Config;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\kamihaya_cms_ai\Form\Config\KamihayaAiSettingsBase;

/**
 * Class Kamihaya AI Document Check Settings.
 */
class KamihayaAiDocumentCheckSettings extends KamihayaAiSettingsBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kamihaya_cms_ai_document_check_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['kamihaya_ai_cms_document_check.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('kamihaya_ai_cms_document_check.settings');

    $form = parent::buildForm($form, $form_state);

    // Waiting movie settings.
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('kamihaya_ai_cms_document_check.settings');
    $form_state->cleanValues();

    parent::submitForm($form, $form_state);

    foreach ($form_state->getValues() as $key => $value) {
      if ($key === 'step_design' | empty($value[0])) {
        continue;
      }
      $file = File::load($value[0]);
      if (!empty($file)) {
        $file->setPermanent();
        $file->save();
      }
    }
  }

}
