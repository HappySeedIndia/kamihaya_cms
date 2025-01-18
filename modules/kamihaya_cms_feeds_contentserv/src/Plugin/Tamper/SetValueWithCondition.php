<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for setting a value or default value.
 *
 * @Tamper(
 *   id = "kamihaya_set_value_with_condition",
 *   label = @Translation("Set value with condition"),
 *   description = @Translation("Set value with condition."),
 *   category = "Text"
 * )
 */
class SetValueWithCondition extends TamperBase {

  const SETTING_CONDITION_SOURCE = 'condition_source';
  const SETTING_CONDITION_VALUE = 'condition_value';
  const SETTING_MATCHING_CONDITION = 'matching_condition';
  const SETTING_OTEHR_TAMPER_CONDITION = 'other_tamper_condition';
  const SETTING_DATA_VALUE = 'data_value';
  const SETTING_NOT_MATCH_VALUE = 'not_match_value';


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_CONDITION_SOURCE] = '';
    $config[self::SETTING_CONDITION_VALUE] = '';
    $config[self::SETTING_MATCHING_CONDITION] = '';
    $config[self::SETTING_OTEHR_TAMPER_CONDITION] = 'and';
    $config[self::SETTING_DATA_VALUE] = '';
    $config[self::SETTING_NOT_MATCH_VALUE] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_CONDITION_SOURCE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Condition source'),
      '#required' => TRUE,
      '#default_value' => $this->getSetting(self::SETTING_CONDITION_SOURCE),
      '#description' => $this->t('The source field to check the condition.'),
    ];

    $form[self::SETTING_CONDITION_VALUE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Condition value'),
      '#required' => TRUE,
      '#default_value' => $this->getSetting(self::SETTING_CONDITION_VALUE),
      '#description' => $this->t('The source value to check the condition.'),
    ];

    $form[self::SETTING_MATCHING_CONDITION] = [
      '#type' => 'radios',
      '#title' => $this->t('Matching condition'),
      '#required' => TRUE,
      '#options' => [
        '==' => $this->t('Equal'),
        '!=' => $this->t('Not equal'),
        'includes' => $this->t('Includes'),
        'not_includes' => $this->t('Not includes'),
      ],
      '#default_value' => $this->getSetting(self::SETTING_MATCHING_CONDITION),
      '#description' => $this->t('The matching condition.'),
    ];

    $form[self::SETTING_OTEHR_TAMPER_CONDITION] = [
      '#type' => 'radios',
      '#title' => $this->t('Other tamper condition'),
      '#required' => TRUE,
      '#options' => [
        'and' => $this->t('And'),
        'or' => $this->t('Or'),
      ],
      '#default_value' => $this->getSetting(self::SETTING_OTEHR_TAMPER_CONDITION),
      '#description' => $this->t('The condition to combine with other tamper.'),
    ];

    $form[self::SETTING_DATA_VALUE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Data value'),
      '#required' => TRUE,
      '#default_value' => $this->getSetting(self::SETTING_DATA_VALUE),
      '#description' => $this->t('The data value if the condition is met.'),
    ];

    $form[self::SETTING_NOT_MATCH_VALUE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Not match value'),
      '#default_value' => $this->getSetting(self::SETTING_NOT_MATCH_VALUE),
      '#description' => $this->t('The data value if the condition is not met.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->setConfiguration([
      self::SETTING_CONDITION_SOURCE => $form_state->getValue(self::SETTING_CONDITION_SOURCE),
      self::SETTING_CONDITION_VALUE => $form_state->getValue(self::SETTING_CONDITION_VALUE),
      self::SETTING_MATCHING_CONDITION => $form_state->getValue(self::SETTING_MATCHING_CONDITION),
      self::SETTING_OTEHR_TAMPER_CONDITION => $form_state->getValue(self::SETTING_OTEHR_TAMPER_CONDITION),
      self::SETTING_DATA_VALUE => $form_state->getValue(self::SETTING_DATA_VALUE),
      self::SETTING_NOT_MATCH_VALUE => $form_state->getValue(self::SETTING_NOT_MATCH_VALUE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    $condition_source = $this->getSetting(self::SETTING_CONDITION_SOURCE);
    $condition_value = $this->getSetting(self::SETTING_CONDITION_VALUE);
    $matching_condition = $this->getSetting(self::SETTING_MATCHING_CONDITION);
    $other_condition = $this->getSetting(self::SETTING_OTEHR_TAMPER_CONDITION);
    $data_value = $this->getSetting(self::SETTING_DATA_VALUE);
    $not_match_value = $this->getSetting(self::SETTING_NOT_MATCH_VALUE);
    $value = $item->getSource()[$condition_source];

    if (!isset($value)) {
      $value = '';
    }

    if ($other_condition === 'and' && $data != $data_value) {
      return $not_match_value;
    }

    if ($other_condition === 'or' && $data == $data_value) {
      return $data_value;
    }

    switch ($matching_condition) {
      case 'includes':
        if (strpos($value, $condition_value) !== FALSE) {
          return $data_value;
        }
        break;

      case 'not_includes':
        if (strpos($value, $condition_value) !== FALSE) {
          return $data_value;
        }
        break;

      default:
        if (eval("return '$value' $matching_condition '$condition_value';")) {
          return $data_value;
        }
        break;
    }

    return $not_match_value;
  }

}
