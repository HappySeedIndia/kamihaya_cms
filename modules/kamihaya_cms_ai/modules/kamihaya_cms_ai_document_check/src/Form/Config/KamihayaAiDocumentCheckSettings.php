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
  protected function getSteps()
  {
    return [
      'summarize' => $this->t('Summarize'),
      'copyright_check' => $this->t('Copyright check'),
      'company_check' => $this->t('Company rule check'),
    ];
  }

}
