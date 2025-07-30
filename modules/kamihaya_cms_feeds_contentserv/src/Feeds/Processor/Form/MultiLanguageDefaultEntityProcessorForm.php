<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Processor\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Feeds\Processor\Form\DefaultEntityProcessorForm;

/**
 * The configuration form for the entity processor.
 */
class MultiLanguageDefaultEntityProcessorForm extends DefaultEntityProcessorForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $entity_type = $this->entityTypeManager->getDefinition($this->plugin->entityType());

    if (!$entity_type->hasKey('langcode') || !in_array($entity_type->id(), ['node', 'media', 'file']) || empty($form['langcode'])) {
      return $form;
    }

    $langcode = $form['langcode'];
    unset($form['langcode']);

    $form = array_merge([
      'langcode' => $langcode,
      'addtional_langcode' => [
        '#type' => 'textfield',
        '#title' => $this->t('Addtional language code'),
        '#description' => $this->t('The language code to addtional language. For example, "en" or "en_US" for English.'),
        '#default_value' => $this->plugin->getConfiguration('addtional_langcode'),
      ],
    ], $form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $langcodes = $this->plugin->languageOptions();
    $addtional_langcode = $form_state->getValue('addtional_langcode');
    $langucode = explode('_', $addtional_langcode)[0];
    if (!empty($addtional_langcode) && !array_key_exists($langucode, $langcodes)) {
      $form_state->setError($form['addtional_langcode'], $this->t('The langcode %label is not correct.', [
        '%label' => $addtional_langcode,
      ]));
    }
  }

}
