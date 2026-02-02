<?php

namespace Drupal\kamihaya_cms_ai\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base controller for Kamihaya AI.
 */
class KamihayaAiControllerBase extends ControllerBase {

  /**
   * Constructs a new KamihayaAiControllerBase object.
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
    return $this->t('Kamihaya AI');
  }

  /**
   * Get the config names.
   *
   * @return string
   *   The config name.
   */
  protected function getConfigNames() {
    return 'kamihaya_cms_ai.settings';
  }

  /**
   * Set the content of the page.
   *
   * @return array
   *   The content of the page.
   */
  public function content() {
    $config = $this->config($this->getConfigNames());

    $background = $config->get('background');
    if (empty($background)) {
      $background = 'color';
    }
    $variables = [
      '#background' => $background,
      '#second_mode' => $config->get('seond_mode') ?: $config->get('seond_mode'),
      '#default_mode_name' => $config->get('default_mode_name') ?: $config->get('default_mode_name'),
      '#second_mode_name' => $config->get('second_mode_name') ?: $config->get('second_mode_name'),
    ];

    if ($background === 'image') {
      $variables['#bg_image'] = $this->fileUrlGenerator->generateString($config->get('bg_image')[0]['uri']);
    }
    if ($background === 'video') {
      $variables['#bg_video'] = $this->fileUrlGenerator->generateString($config->get('bg_video')[0]['uri']);
    }

    $steps = $this->getSteps();
    foreach ($steps as $key => &$value) {
      // Get the waiting movie.
      $fid = $config->get($key);
      if (!empty($fid)) {
        /** @var \Drupal\file\FileInterface $file */
        $file = $this->entityTypeManager->getStorage('file')->load($fid[0]);
        if ($file instanceof FileInterface) {
          $value['wait_movie'] = $this->fileUrlGenerator->generate($file->getFileUri());
        }
      }

      // Get the process image.
      $fid = $config->get("{$key}_image");
      if (!empty($fid)) {
        /** @var \Drupal\file\FileInterface $file */
        $file = $this->entityTypeManager->getStorage('file')->load($fid[0]);
        if ($file instanceof FileInterface) {
          $value['process_image'] = '';
          continue;
        }
        $value['process_image'] = $this->fileUrlGenerator->generate($file->getFileUri());
      }
    }

    $position = [];
    foreach (array_keys($steps) as $key) {
      if (empty($config->get("{$key}_x_left"))) {
        continue;
      }
      $position[$key] = [
        'x_left' => $config->get("{$key}_x_left"),
        'x_right' => $config->get("{$key}_x_right"),
        'y_top' => $config->get("{$key}_y_top"),
        'y_bottom' => $config->get("{$key}_y_bottom"),
      ];
    }

    $variables['#steps'] = $steps;
    $variables['#hide_result'] = TRUE;
    $variables['#stoppable'] = TRUE;
    $variables['#process'] = TRUE;
    $variables['#attached']['drupalSettings']['process_image_position'] = $position;

    return ['#theme' => 'kamihaya_ai'] + $variables;
  }

  /**
   * Get the steps.
   *
   * @return array
   *   The steps.
   */
  protected function getSteps() {
    return [
      'new' => [],
      'task' => [],
      'start' => [],
      'complete' => [],
      'error' => [],
    ];
  }

}
