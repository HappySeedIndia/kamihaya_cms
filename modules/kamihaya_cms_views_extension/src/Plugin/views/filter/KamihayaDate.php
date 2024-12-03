<?php

namespace Drupal\kamihaya_cms_views_extension\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\views\filter\Date;
use Drupal\views\Attribute\ViewsFilter;

/**
 * Extend the date filter.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("kamihaya_date")]
class KamihayaDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['type'] = ['default' => 'textfield'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Input type'),
      '#options' => ['textfield' => $this->t('Textfield'), 'checkbox' => $this->t('Checkbox')],
      '#default_value' => $this->options['type'],
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
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
    if (empty($this->options['exposed'])) {
      return;
    }
    if ($this->options['type'] === 'textfield') {
      return;
    }
    if ($this->options['type'] === 'checkbox'
      && (!isset($this->options['value']['value']) && (!isset($this->options['value']['min']) || !isset($this->options['value']['max'])))) {
      $this->options['type'] === 'textfield';
      return;
    }

    $value = $this->options['expose']['identifier'];
    $wrapper = $value . '_wrapper';
    if (!empty($form[$wrapper])) {
      $form[$value] = [
        '#title' => $form[$wrapper]['#title'],
      ];
      unset($form[$wrapper]);
    }
    if (!empty($form[$value])) {
      $form[$value]['#type'] = 'checkbox';
      $form[$value]['#default_value'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $identifier = $this->options['expose']['identifier'];
    $user_input = $form_state->getUserInput();

    $checked = TRUE;
    if ($form_state->get('exposed') && !isset($user_input[$identifier])) {
      $checked = FALSE;
    }
    else {
      $user_input[$identifier] = $this->options['value'];
      $form_state->setUserInput($user_input);
    }
    parent::valueForm($form, $form_state);

    if (!$form_state->get('exposed')) {
      return;
    }

    if ($this->options['type'] === 'textfield') {
      return;
    }

    if ($this->options['type'] === 'checkbox'
      && (!isset($this->options['value']['value']) && (!isset($this->options['value']['min']) || !isset($this->options['value']['max'])))) {
      $this->options['type'] === 'textfield';
      return;
    }

    if (!$checked) {
      $user_input[$identifier] = FALSE;
      $form_state->setUserInput($user_input);
    }
    $form['value'] = [
      '#type' => 'checkbox',
      '#title' => $this->options['expose']['label'],
      '#default_value' => $checked,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    $key = $this->isAGroup() ? 'group_info' : 'expose';
    if (empty($this->options[$key]['identifier'])) {
      return FALSE;
    }
    $value = &$input[$this->options[$key]['identifier']];
    if (empty($value) && $this->options['type'] !== 'checkbox') {
      return FALSE;
    }

    $input[$this->options[$key]['identifier']] = !empty($value)
      ? $this->options['value']
      : [
        'value' => FALSE,
        'min' => FALSE,
        'max' => FALSE,
      ];
    return parent::acceptExposedInput($input);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (empty($this->value['value']) && empty($this->value['min']) && empty($this->value['max'])) {
      return;
    }

    if ($this->options['type'] === 'textfield') {
      parent::query();
      return;
    }

    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

}
