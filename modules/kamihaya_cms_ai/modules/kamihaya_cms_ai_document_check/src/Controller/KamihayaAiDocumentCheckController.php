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
   * Set the content of the page.
   *
   * @return array
   *   The content of the page.
   */
  public function content() {
    $theme = parent::content();
    $config = $this->config('kamihaya_cms_ai_document_check.settings');

    // Get the summarize wait movie.
    $fid = $config->get('summarize');
    if (!empty($fid)) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid[0]);
      if (!empty($file)) {
        $summarize = $this->fileUrlGenerator->generate($file->getFileUri());
      }
    }

    // Get the copyright check wait movie.
    $fid = $config->get('copyright_check');
    if (!empty($fid)) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid[0]);
      if (!empty($file)) {
        $copyright_check = $this->fileUrlGenerator->generate($file->getFileUri());
      }
    }

    // Get the company check wait movie.
    $fid = $config->get('company_check');
    if (!empty($fid)) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid[0]);
      if (!empty($file)) {
        $company_check = $this->fileUrlGenerator->generate($file->getFileUri());
      }
    }

        // Get the proccess images.
    // New.
    $fid = $config->get('new_image');
    if (!empty($fid)) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid[0]);
      if (!empty($file)) {
        $new = $this->fileUrlGenerator->generate($file->getFileUri());
      }
    }

    // Start.
    $fid = $config->get('start_image');
    if (!empty($fid)) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid[0]);
      if (!empty($file)) {
        $start = $this->fileUrlGenerator->generate($file->getFileUri());
      }
    }

    // Summarize.
    $fid = $config->get('summarize_image');
    if (!empty($fid)) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid[0]);
      if (!empty($file)) {
        $summarize_image = $this->fileUrlGenerator->generate($file->getFileUri());
      }
    }

    // Copyright check.
    $fid = $config->get('copyright_check_image');
    if (!empty($fid)) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid[0]);
      if (!empty($file)) {
        $copyright_check_image = $this->fileUrlGenerator->generate($file->getFileUri());
      }
    }

    // Company check.
    $fid = $config->get('company_check_image');
    if (!empty($fid)) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid[0]);
      if (!empty($file)) {
        $company_check_image = $this->fileUrlGenerator->generate($file->getFileUri());
      }
    }

    // Complete.
    $fid = $config->get('complete_image');
    if (!empty($fid)) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid[0]);
      if (!empty($file)) {
        $complete = $this->fileUrlGenerator->generate($file->getFileUri());
      }
    }

    // Error.
    $fid = $config->get('error_image');
    if (!empty($fid)) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid[0]);
      if (!empty($file)) {
        $error = $this->fileUrlGenerator->generate($file->getFileUri());
      }
    }

    $theme['#steps'] = [
      'new' => [
        'process_image' => $new ?? '',
      ],
      'start' => [
        'process_image' => $start ?? '',
      ],
      'summarize' => [
        'name' => $this->t('Summarize'),
        'wait_movie' => $summarize ?? '',
        'process_image' => $summarize_image ?? '',
      ],
      'copyright_check' => [
        'name' => $this->t('Copyright check'),
        'wait_movie' => $copyright_check ?? '',
        'process_image' => $copyright_check_image ?? '',
      ],
      'company_check' => [
        'name' => $this->t('Company rule check'),
        'wait_movie' => $company_check ?? '',
        'process_image' => $company_check_image ?? '',
      ],
      'complete' => [
        'process_image' => $complete ?? '',
      ],
      'error' => [
        'process_image' => $error ?? '',
      ],
    ];
    $first_screne = $this->formBuilder->getForm(FileUploadForm::class);
    $theme['#chat_body'] = $first_screne;
    $process_form = $this->formBuilder->getForm(ProcessForm::class);
    $theme['#process_form'] = $process_form;
    $theme['#hide_result'] = TRUE;
    $theme['#step_design'] = $config->get('step_design');
    $theme['#step_completed'] = $this->t('Check completed');
    $theme['#stoppable'] = TRUE;
    $theme['#process'] = TRUE;
    $theme['#attached']['library'][] = 'kamihaya_cms_ai_document_check/kamihaya_ai.document_check';
    $theme['#attached']['drupalSettings']['ajax_url'] = 'ajax-handler-document-check';
    $theme['#attached']['drupalSettings']['process_name'] = $this->t('Contract');
    $theme['#attached']['drupalSettings']['task_name'] = $this->t('Contract drafting');
    $theme['#attached']['drupalSettings']['last_task_name'] = $this->t('Receiving the proposal');
    return $theme;
  }

}
