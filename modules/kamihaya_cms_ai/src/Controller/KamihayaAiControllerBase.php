<?php

namespace Drupal\kamihaya_cms_ai\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Base controller for Kamihaya AI.
 */
class KamihayaAiControllerBase extends ControllerBase {

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
   * Set the content of the page.
   *
   * @return array
   *   The content of the page.
   */
  public function content() {
    $config = $this->config('kamihaya_cms_ai.settings');
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
      $variables['#bg_image'] = file_create_url($config->get('bg_image')[0]['uri']);
    }
    if ($background === 'video') {
      $variables['#bg_video'] = file_create_url($config->get('bg_video')[0]['uri']);
    }
    return ['#theme' => 'kamihaya_ai'] + $variables;
  }

}
