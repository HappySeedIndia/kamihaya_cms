<?php

namespace Drupal\kamihaya_cms_ai_loan_proposal_draft\Controller;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\kamihaya_cms_ai\Controller\KamihayaAiControllerBase;
use Drupal\kamihaya_cms_ai_loan_proposal_draft\Form\CompanySelectForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for handling the loan proposal draft.
 */
class KamihayaAiLoanProposalDraftController extends KamihayaAiControllerBase {

  /**
   * Constructs a new KamihayaAiLoanProposalDraftController object.
   *
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file system.
   */
  public function __construct(
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {
    $this->formBuilder();
    $this->entityTypeManager();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_url_generator'),
    );
  }

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
      'start' => [
        'process_image' => $start ?? '',
      ],
      'summarize' => [
        'name' => $this->t('Summarize'),
        'wait_movie' => $summarize ?? '',
      ],
      'draft' => [
        'name' => $this->t('Loan Proposal Draft'),
        'wait_movie' => $draft ?? '',
      ],
      'complete' => [
        'process_image' => $complete ?? '',
      ],
      'error' => [
        'process_image' => $error ?? '',
      ],
    ];
    $first_screne = \Drupal::formBuilder()->getForm(CompanySelectForm::class);
    $theme['#chat_body'] = $first_screne;
    $theme['#hide_result'] = TRUE;
    $theme['#step_design'] = $config->get('step_design');
    $theme['#stoppable'] = TRUE;
    $theme['#process'] = TRUE;
    $theme['#edit_prompt'] = TRUE;
    $theme['#attached']['library'][] = 'kamihaya_cms_ai_loan_proposal_draft/kamihaya_ai.loan_proposal_draft';
    $theme['#attached']['drupalSettings']['ajax_url'] = 'ajax-handler-loan-proposal-draft';
    return $theme;
  }

}
