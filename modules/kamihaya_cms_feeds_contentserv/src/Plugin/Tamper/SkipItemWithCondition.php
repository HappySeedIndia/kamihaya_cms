<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Plugin\Tamper;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\SkipTamperItemException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for skipping item with condition.
 *
 * @Tamper(
 *   id = "kamihaya_skip_item_with_condition",
 *   label = @Translation("Skip item with condition"),
 *   description = @Translation("Skip item with condition."),
 *   category = "Filter"
 * )
 */
class SkipItemWithCondition extends TamperBase implements KamihayaTamperInterface {

  const SETTING_CONDITION_VALUE = 'condition_value';
  const SETTING_MATCHING_CONDITION = 'matching_condition';
  const SETTING_SKIP_EMPTY = 'skip_empty';
  const SETTING_SKIP_CONDITION = 'skip_condition';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_CONDITION_VALUE] = '';
    $config[self::SETTING_MATCHING_CONDITION] = '';
    $config[self::SETTING_SKIP_EMPTY] = TRUE;
    $config[self::SETTING_SKIP_CONDITION] = TRUE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
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

    $form[self::SETTING_SKIP_EMPTY] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip item with empty value'),
      '#default_value' => $this->getSetting(self::SETTING_SKIP_EMPTY),
      '#description' => $this->t("If checked, skip the data when the data is empty."),
    ];

    $form[self::SETTING_SKIP_CONDITION] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip condition'),
      '#default_value' => $this->getSetting(self::SETTING_SKIP_CONDITION),
      '#description' => $this->t("If checked, skip the data only when the data doesn't exist."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->setConfiguration([
      self::SETTING_CONDITION_VALUE => $form_state->getValue(self::SETTING_CONDITION_VALUE),
      self::SETTING_MATCHING_CONDITION => $form_state->getValue(self::SETTING_MATCHING_CONDITION),
      self::SETTING_SKIP_EMPTY => $form_state->getValue(self::SETTING_SKIP_EMPTY),
      self::SETTING_SKIP_CONDITION => $form_state->getValue(self::SETTING_SKIP_CONDITION),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    $skip_condition = $this->getSetting(self::SETTING_SKIP_CONDITION);
    if ($skip_condition) {
      return $data;
    }
    $condition_value = $this->getSetting(self::SETTING_CONDITION_VALUE);
    $matching_condition = $this->getSetting(self::SETTING_MATCHING_CONDITION);
    $skip_empty = $this->getSetting(self::SETTING_SKIP_EMPTY);

    if (!isset($data)) {
      $data = '';
    }

    if ($skip_empty && strlen($data) === 0) {
      throw new SkipTamperItemException("Skip item with empty value.");
    }

    switch ($matching_condition) {
      case 'includes':
        if (strpos($data, $condition_value) === FALSE) {
          throw new SkipTamperItemException("Skip item with condition: $matching_condition $condition_value.");
        }
        break;

      case 'not_includes':
        if (strpos($data, $condition_value) !== FALSE) {
          throw new SkipTamperItemException("Skip item with condition: $matching_condition $condition_value.");
        }
        break;

      default:
        if (eval("return '$data' $matching_condition '$condition_value';")) {
          throw new SkipTamperItemException("Skip item with condition: $matching_condition $condition_value.");
        }
        break;
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function preSavetamper(EntityInterface $entity, ?TamperableItemInterface $item = NULL, $source) {
    $skip_condition = $this->getSetting(self::SETTING_SKIP_CONDITION);
    if ($skip_condition && !$entity->isNew()) {
      return;
    }
    $condition_value = $this->getSetting(self::SETTING_CONDITION_VALUE);
    $matching_condition = $this->getSetting(self::SETTING_MATCHING_CONDITION);
    $skip_empty = $this->getSetting(self::SETTING_SKIP_EMPTY);
    $value = $item->getSource()[$source];

    if (!isset($value)) {
      $value = '';
    }

    if ($skip_empty && strlen($value) === 0) {
      throw new SkipTamperItemException("Skip item with empty value.");
    }

    switch ($matching_condition) {
      case 'includes':
        if (strpos($value, $condition_value) === FALSE) {
          throw new SkipTamperItemException("Skip item with condition: $source $matching_condition $condition_value.");
        }
        break;

      case 'not_includes':
        if (strpos($value, $condition_value) !== FALSE) {
          throw new SkipTamperItemException("Skip item with condition: $source $matching_condition $condition_value.");
        }
        break;

      default:
        if (eval("return '$value' $matching_condition '$condition_value';")) {
          throw new SkipTamperItemException("Skip item with condition: $source $matching_condition $condition_value.");
        }
        break;
    }
  }

}
