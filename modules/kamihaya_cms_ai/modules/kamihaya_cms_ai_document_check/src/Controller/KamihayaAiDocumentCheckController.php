<?php

namespace Drupal\kamihaya_cms_ai_document_check\Controller;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\kamihaya_cms_ai\Controller\KamihayaAiControllerBase;
use Drupal\kamihaya_cms_ai_document_check\Form\FileUploadForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for handling the document check.
 */
class KamihayaAiDocumentCheckController extends KamihayaAiControllerBase {

  /**
   * Constructs a new KamihayaAiDocumentCheckController object.
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

    $theme['#steps'] = [
      'summarize' => [
        'name' => $this->t('Summarize'),
        'wait_movie' => $summarize ?? '',
      ],
      'copyright_check' => [
        'name' => $this->t('Copyright check'),
        'wait_movie' => $copyright_check ?? '',
      ],
      'company_check' => [
        'name' => $this->t('Company rule check'),
        'wait_movie' => $company_check ?? '',
      ],
    ];
    $first_screne = \Drupal::formBuilder()->getForm(FileUploadForm::class);
    $theme['#chat_body'] = $first_screne;
    $theme['#hide_result'] = TRUE;
    $theme['#step_design'] = $config->get('step_design');
    $theme['#step_completed'] = $this->t('Check completed');
    $theme['#stoppable'] = TRUE;
    $theme['#attached']['library'][] = 'kamihaya_cms_ai_document_check/kamihaya_ai.document_check';
    return $theme;
  }

}
