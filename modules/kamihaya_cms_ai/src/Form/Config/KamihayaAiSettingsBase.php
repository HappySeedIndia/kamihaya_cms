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

}
