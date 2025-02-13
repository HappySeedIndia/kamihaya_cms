<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Plugin\Tamper;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
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
    $form[self::SETTING_MATCHING_CONDITION] = [
      '#type' => 'radios',
      '#title' => $this->t('Matching condition'),
      '#required' => TRUE,
      '#options' => [
        '==' => $this->t('Equal'),
        '!=' => $this->t('Not equal'),
        'includes' => $this->t('Includes'),
        'not_includes' => $this->t('Not includes'),
        'empty' => $this->t('Empty'),
        'not_empty' => $this->t('Not empty'),
      ],
      '#default_value' => $this->getSetting(self::SETTING_MATCHING_CONDITION),
      '#description' => $this->t('The matching condition.'),
    ];


    $form[self::SETTING_CONDITION_VALUE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Condition value'),
      '#default_value' => $this->getSetting(self::SETTING_CONDITION_VALUE),
      '#description' => $this->t('The source value to check the condition.'),
      '#states' => [
        'invisible' => [
          'input[name="plugin_configuration[matching_condition]"]' =>  [
            ['value' => 'empty'],
            'or',
            ['value' => 'not_empty'],
          ],
        ],
        'required' => [
          'input[name="plugin_configuration[matching_condition]"]' => [
            ['value' => '=='],
            'or',
            ['value' => '!='],
            'or',
            ['value' => 'includes'],
            'or',
            ['value' => 'not_includes'],
          ],
        ],
      ],
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

    if ($skip_empty && ((is_array($data) && empty($data)) || (!is_array($data) && strlen($data))) === 0) {
      throw new SkipTamperItemException("Skip item with empty value.");
    }

    switch ($matching_condition) {
      case 'includes':
        if ((is_array($data) && in_array($condition_value, $data)) || (!is_array($data) && strpos($data, $condition_value) !== FALSE)) {
          throw new SkipTamperItemException("Skip item with condition: $matching_condition $condition_value.");
        }
        break;

      case 'not_includes':
        if ((is_array($data) && !in_array($condition_value, $data)) || (!is_array($data) && strpos($data, $condition_value) === FALSE)) {
          throw new SkipTamperItemException("Skip item with condition: $matching_condition $condition_value.");
        }
        break;

      case 'empty':
        if ((is_array($data) && empty($data)) || (!is_array($data) && strlen($data) == 0)) {
          throw new SkipTamperItemException("Skip item with condition: $matching_condition.");
        }
        break;

      case 'not_empty':
        if ((is_array($data) && !empty($data)) || (!is_array($data) && strlen($data) != 0)) {
          throw new SkipTamperItemException("Skip item with condition: $matching_condition.");
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
  public function postParseTamper(FeedInterface $feed, $data, TamperableItemInterface $item) {
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveTamper(FeedInterface $feed, EntityInterface $entity, ?TamperableItemInterface $item, $source) {
    $skip_condition = $this->getSetting(self::SETTING_SKIP_CONDITION);
    if ($skip_condition && !$entity->isNew()) {
      return;
    }
    $condition_value = $this->getSetting(self::SETTING_CONDITION_VALUE);
    $matching_condition = $this->getSetting(self::SETTING_MATCHING_CONDITION);
    $skip_empty = $this->getSetting(self::SETTING_SKIP_EMPTY);
    $value = array_key_exists($source, $item->getSource()) ? $item->getSource()[$source] : '';

    if (!isset($value)) {
      $value = '';
    }

    $label = $entity->label();
    $type = $entity->getEntityTypeId();
    if ($skip_empty && ((is_array($value) && empty($value)) || (!is_array($value) && strlen($value))) === 0) {
      throw new SkipTamperItemException(strtr("@name - Skip item[ type: @type, name: @label, source: @source ] with empty value.", [
        '@name' => $feed->label(),
        '@label' => $label,
        '@type' => $type,
        '@source' => $source
      ]));
    }

    switch ($matching_condition) {
      case 'includes':
        if ((is_array($value) && in_array($condition_value, $value)) || (!is_array($value) && strpos($value, $condition_value) !== FALSE)) {
          throw new SkipTamperItemException(strtr("@name - Skip item[ type: @type, name: @label ] with condition: @source @condition @value.", [
            '@name' => $feed->label(),
            '@label' => $label,
            '@type' => $type,
            '@source' => $source,
            '@condition' => $matching_condition,
            '@value' => $condition_value,
          ]));
        }
        break;

      case 'not_includes':
        if ((is_array($value) && !in_array($condition_value, $value)) || (!is_array($value) && strpos($value, $condition_value) === FALSE)) {
          throw new SkipTamperItemException(strtr("@name - Skip item[ type: @type, name: @label ] with condition: @source @condition @value.", [
            '@name' => $feed->label(),
            '@label' => $label,
            '@type' => $type,
            '@source' => $source,
            '@condition' => $matching_condition,
            '@value' => $condition_value,
          ]));
        }
        break;

      case 'empty':
        if ((is_array($value) && empty($value)) || (!is_array($value) && strlen($value) == 0)) {
          throw new SkipTamperItemException(strtr("@name - Skip item[ type: @type, name: @label ] with condition: @condition.", [
            '@name' => $feed->label(),
            '@label' => $label,
            '@type' => $type,
            '@condition' => $matching_condition,
          ]));
        }
        break;

      case 'not_empty':
        if ((is_array($value) && !empty($value)) || (!is_array($value) && strlen($value) != 0)) {
          throw new SkipTamperItemException(strtr("@name - Skip item[ type: @type, name: @label ] with condition: @condition.", [
            '@name' => $feed->label(),
            '@label' => $label,
            '@type' => $type,
            '@condition' => $matching_condition,
          ]));
        }
        break;

      default:
        if (eval("return '$value' $matching_condition '$condition_value';")) {
          throw new SkipTamperItemException(strtr("@name - Skip item[ type: @type, name: @label ] with condition: @source @condition @value.", [
            '@name' => $feed->label(),
            '@label' => $label,
            '@type' => $type,
            '@source' => $source,
            '@condition' => $matching_condition,
            '@value' => $condition_value,
          ]));
        }
        break;
    }
  }

}
