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
    return ['kamihaya_cms_ai_document_check.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('kamihaya_cms_ai_document_check.settings');
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

  /**
   * {@inheritdoc}
   */
  protected function getSteps()
  {
    return [
      'summarize' => $this->t('Summarize'),
      'copyright_check' => $this->t('Copyright check'),
      'company_check' => $this->t('Company rule check'),
    ];
  }

}
