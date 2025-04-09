<?php

namespace Drupal\kamihaya_cms_ai_loan_proposal_draft\Controller;

use Drupal\kamihaya_cms_ai\Controller\KamihayaAiControllerBase;
use Drupal\kamihaya_cms_ai_document_check\Form\ProcessForm;
use Drupal\kamihaya_cms_ai_loan_proposal_draft\Form\CompanySelectForm;

/**
 * Controller for handling the loan proposal draft.
 */
class KamihayaAiLoanProposalDraftController extends KamihayaAiControllerBase {

  /**
   * Set the title of the page.
   *
   * @return string
   *   The title of the page.
   */
  public function title() {
    return $this->t('Loan Proposal Draft');
  }

  /**
   * Set the content of the page.
   *
   * @return array
   *   The content of the page.
   */
  public function content() {
    $theme = parent::content();
    $config = $this->config('kamihaya_cms_ai_loan_proposal_draft.settings');

    // Get the summarize wait movie.
    $fid = $config->get('summarize');
    if (!empty($fid)) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid[0]);
      if (!empty($file)) {
        $summarize = $this->fileUrlGenerator->generate($file->getFileUri());
      }
    }

    // Get the draft wait movie.
    $fid = $config->get('draft');
    if (!empty($fid)) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid[0]);
      if (!empty($file)) {
        $draft = $this->fileUrlGenerator->generate($file->getFileUri());
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

    // Draft.
    $fid = $config->get('draft_image');
    if (!empty($fid)) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid[0]);
      if (!empty($file)) {
        $draft_image = $this->fileUrlGenerator->generate($file->getFileUri());
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
      'draft' => [
        'name' => $this->t('Loan Proposal Draft'),
        'wait_movie' => $draft ?? '',
        'process_image' => $draft_image ?? '',
      ],
      'complete' => [
        'process_image' => $complete ?? '',
      ],
      'error' => [
        'process_image' => $error ?? '',
      ],
    ];
    $first_screne = $this->formBuilder->getForm(CompanySelectForm::class);
    $theme['#chat_body'] = $first_screne;
    $process_form = $this->formBuilder->getForm(ProcessForm::class);
    $theme['#process_form'] = $process_form;
    $theme['#hide_result'] = TRUE;
    $theme['#step_design'] = $config->get('step_design');
    $theme['#stoppable'] = TRUE;
    $theme['#process'] = TRUE;
    $theme['#edit_prompt'] = TRUE;
    $theme['#attached']['library'][] = 'kamihaya_cms_ai_loan_proposal_draft/kamihaya_ai.loan_proposal_draft';
    $theme['#attached']['drupalSettings']['ajax_url'] = 'ajax-handler-loan-proposal-draft';
    $theme['#attached']['drupalSettings']['process_name'] = $this->t('Loan proposal');
    $theme['#attached']['drupalSettings']['task_name'] = $this->t('Loan proposal drafting');
    $theme['#attached']['drupalSettings']['last_task_name'] = $this->t('Research on historical records and borrower companies');
    return $theme;
  }

}
