<?php

namespace Drupal\kamihaya_cms_ai\Form\Config;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Kamihaya AI Settings base.
 */
abstract class KamihayaAiSettingsBase extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable($this->getEditableConfigNames()[0]);

    // Options settings.
    $form['design'] = [
      '#type' => 'details',
      '#title' => $this->t('Design'),
      '#open' => TRUE,
    ];

    $form['design']['step_design'] = [
      '#type' => 'radios',
      '#title' => $this->t('Step design'),
      '#options' => [
        'tabs' => $this->t('Tabs'),
        'slider' => $this->t('Slider'),
      ],
      '#default_value' => $config->get('step_design'),
    ];

    $steps = $this->getSteps();
    if (empty($steps)) {
      return parent::buildForm($form, $form_state);
    }

    // Waiting movie settings.
    $form['waiging_movie'] = [
      '#type' => 'details',
      '#title' => $this->t('Waiting Movie'),
      '#description' => $this->t('Upload a movie file for waiting .'),
      '#open' => TRUE,
    ];

    foreach ($steps as $key => $step) {
      $form['waiging_movie'][$key] = [
        '#type' => 'managed_file',
        '#title' => $step,
        '#upload_location' => 'public://waiting_movie/',
        '#default_value' => $config->get($key),
        '#upload_validators' => [
          'file_validate_extensions' => ['mp4'],
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable($this->getEditableConfigNames()[0]);
    $form_state->cleanValues();

    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get steps.
   */
  protected function getSteps() {
    return [];
  }

}
