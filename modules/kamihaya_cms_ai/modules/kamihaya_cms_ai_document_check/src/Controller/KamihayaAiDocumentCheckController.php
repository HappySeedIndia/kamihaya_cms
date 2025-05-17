<?php

namespace Drupal\kamihaya_cms_ai_document_check\Controller;

use Drupal\kamihaya_cms_ai\Controller\KamihayaAiControllerBase;
use Drupal\kamihaya_cms_ai_document_check\Form\FileUploadForm;
use Drupal\kamihaya_cms_ai_document_check\Form\ProcessForm;

/**
 * Controller for handling the document check.
 */
class KamihayaAiDocumentCheckController extends KamihayaAiControllerBase {

  /**
   * Set the title of the page.
   *
   * @return string
   *   The title of the page.
   */
  public function title() {
    return $this->t('Document Check');
  }

  /**
   * Get the config names.
   *
   * @return string
   *   The config name.
   */
  protected function getConfigNames() {
    return 'kamihaya_cms_ai_document_check.settings';
  }

  /**
   * Set the content of the page.
   *
   * @return array
   *   The content of the page.
   */
  public function content() {
    $theme = parent::content();
    $config = $this->config($this->getConfigNames());

    $first_screne = $this->formBuilder->getForm(FileUploadForm::class);
    $theme['#chat_body'] = $first_screne;
    $process_form = $this->formBuilder->getForm(ProcessForm::class);
    $theme['#process_form'] = $process_form;
    $theme['#step_design'] = $config->get('step_design');
    $theme['#step_completed'] = $this->t('Check completed');
    $theme['#attached']['library'][] = 'kamihaya_cms_ai_document_check/kamihaya_ai.document_check';
    $theme['#attached']['drupalSettings']['ajax_url'] = 'ajax-handler-document-check';
    $theme['#attached']['drupalSettings']['process_name'] = $this->t('Contract');
    $theme['#attached']['drupalSettings']['task_name'] = $this->t('Contract drafting');
    $theme['#attached']['drupalSettings']['last_task_name'] = $this->t('Receiving the proposal');
    return $theme;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSteps() {
    $steps = parent::getSteps();
    $steps['summarize'] = [
      'name' => $this->t('Summarize'),
    ];
    $steps['copyright_check'] = [
      'name' => $this->t('Copyright check'),
    ];
    $steps['company_check'] = [
      'name' => $this->t('Company rule check'),
    ];
    return $steps;
  }
}
