<?php

namespace Drupal\kamihaya_cms_views_extension\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Extend the boolean filter.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("kamihaya_boolean")]
class KamihayaBoolean extends BooleanOperator {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['type'] = ['default' => 'default'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Input type'),
      '#options' => ['default' => $this->t('Default'), 'checkbox' => $this->t('Checkbox')],
      '#default_value' => $this->options['type'],
      '#description' => $this->t('Select the input type for the filter. If checkbox is selected, set this filter as Default on Better exposed filter.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function hasExtraOptions() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    if (!$form_state->get('exposed') || $this->options['type'] === 'default') {
      parent::valueForm($form, $form_state);
      return;
    }

    if (empty($this->valueOptions)) {
      // Initialize the array of possible values for this filter.
      $this->getValueOptions();
    }

    $identifier = $this->options['expose']['identifier'];
    $user_input = $form_state->getUserInput();
    if (!isset($user_input[$identifier])) {
      $user_input[$identifier] = FALSE;
      $form_state->setUserInput($user_input);
      $this->value = FALSE;
    }

    $form['value'] = [
      '#type' => 'checkbox',
      '#title' => $this->value_value,
      '#default_value' => $this->value,
    ];

  }

  /**
   * {@inheritdoc}
   */
  protected function queryOpBoolean($field, $query_operator = self::EQUAL) {
    if ($this->options['type'] === 'default' || empty($this->options['exposed'])) {
      parent::queryOpBoolean($field, $query_operator);
      return;
    }
    $value = $this->value === $this->options['value'] ? $this->options['value'] : !$this->options['value'];
    $this->query->addWhere($this->options['group'], $field, $value, $query_operator);
  }

}
