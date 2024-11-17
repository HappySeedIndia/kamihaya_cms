<?php

namespace Drupal\kamihaya_cms_views_extension\Trait;

use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a the query with the limited display depth.
 */
trait KamihayaTaxonomyViewsFilterTrait {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['display_depth'] = ['default' => 0];
    $options['reduce_by_relation'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildExtraOptionsForm($form, $form_state);
    $form['display_depth'] = [
      '#type' => 'select',
      '#options' => [
        0 => $this->t('Unlimited'),
        1 => $this->t('1'),
        2 => $this->t('2'),
        3 => $this->t('3'),
      ],
      '#title' => $this->t('Display Depth'),
      '#default_value' => $this->options['display_depth'],
      '#states' => [
        'visible' => [
          ':input[name="options[type]"]' => ['value' => 'select'],
        ],
      ],
    ];
    $form['reduce_by_relation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reduce by relation'),
      '#default_value' => $this->options['reduce_by_relation'],
      '#description' => $this->t('Reduce options if they have no relationship with the selected value of other filters .'),
      '#states' => [
        'visible' => [
          ':input[name="options[type]"]' => ['value' => 'select'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $vocabulary = $this->vocabularyStorage->load($this->options['vid']);
    // Let the parent class generate the base form.
    parent::valueForm($form, $form_state);
    if (($this->options['type'] !== 'select')
      || !$form_state->get('exposed')
      || empty($form['value']['#options'])
      || (empty($this->options['display_depth']) && empty($this->options['reduce_by_relation']))) {
      return;
    }

    if (empty($this->options['display_depth']) && !empty($this->options['reduce_by_relation'])) {
      $this->reduceTermByRelation($form, $form['value']['#options']);
      return;
    }

    $tree = $this->termStorage->loadTree($vocabulary->id(), 0, $this->options['display_depth'], TRUE);
    if (empty($tree)) {
      return;
    }
    $options = [];
    foreach ($tree as $term) {
      if (!empty($this->options['hierarchy'])) {
        $choice = new \stdClass();
        $choice->option = [$term->id() => str_repeat('-', $term->depth) . \Drupal::service('entity.repository')->getTranslationFromContext($term)->label()];
        $options[] = $choice;
      }
      else {
        $options[$term->id()] = \Drupal::service('entity.repository')->getTranslationFromContext($term)->label();
      }
    }
    if (empty($options)) {
      return;
    }
    $form['value']['#options'] = $options;
  }

  /**
   * Reduce the options by the relation with other filters.
   *
   * @param array $form
   *   The form array.
   * @param array $options
   *   The options array.
   */
  private function reduceTermByRelation(array &$form, array $options) {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('taxonomy_term', $this->options['vid']);
    $bundles = [];
    $form['value']['#related_filter'] = [];
    foreach ($field_definitions as $field_name => $field_definition) {
      if (strpos($field_name, 'field_') !== 0 || $field_definition->getType() !== 'entity_reference' || strpos($field_definition->getSetting('handler'), 'taxonomy_term') === FALSE) {
        continue;
      }
      $bundles[$field_name] = reset($field_definition->getSetting('handler_settings')['target_bundles']);
    }
    $request_params = array_merge($this->request->query->all(), $this->request->request->all());
    $filters = [];
    foreach ($this->view->filter as $name => $filter) {
      if (strpos($filter->options['plugin_id'], 'taxonomy_index_tid') === FALSE || !in_array($filter->options['vid'], $bundles)) {
        continue;
      }
      $form['value']['#related_filter'][] = $name;
      if (!empty($request_params[$name])) {
        $filters[] = $name;
      }
    }
    if (empty($filters)) {
      $form['value']['#options'] = [];
      return;
    }
    foreach ($filters as $filter) {
      $param_value = is_array($request_params[$filter])
      ? $request_params[$filter]
      : [$request_params[$filter]];

      foreach ($options as $key => $option) {
        $tid = $key;
        if (is_array($option)) {
          $tid = key($option);
        }
        $term = $this->termStorage->load($tid);
        foreach ($bundles as $field_name => $bundle) {
          $field = $term->get($field_name);
          if (empty($field)) {
            unset($options[$key]);
          }
          $target_ids = $field->getValue();
          foreach ($target_ids as $target_id) {
            if (in_array($target_id['target_id'], $param_value)) {
              continue 2;
            }
          }
          unset($options[$key]);
        }
      }
    }
    $form['value']['#options'] = $options;
  }

}
