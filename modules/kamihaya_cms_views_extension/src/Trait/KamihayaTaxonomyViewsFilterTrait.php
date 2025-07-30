<?php

namespace Drupal\kamihaya_cms_views_extension\Trait;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Builds the query with the limited display depth.
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
    // Add an option to hide the filter if it has no selectable options.
    $form['hide_if_empty_options'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide if options are empty'),
      '#default_value' => $this->options['hide_if_empty_options'] ?? FALSE,
      '#description' => $this->t('Hide this exposed filter if no selectable options are available.'),
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

    // Hide the filter if it has no available options.
    $form['value']['#hide_if_empty_options'] = !empty($this->options['hide_if_empty_options']) ? $this->options['hide_if_empty_options'] : FALSE;
    if (!empty($this->options['hide_if_empty_options']) && empty($form['value']['#options'])) {
      if (empty($this->options['reduce_by_relation'])) {
        $form['value']['#access'] = FALSE;
        return;
      }
      $form['value']['#wrapper_attributes']['class'][] = 'hidden';
      return;
    }

    if (($this->options['type'] !== 'select')
      || !$form_state->get('exposed')
      || empty($form['value']['#options'])
      || (empty($this->options['display_depth']) && empty($this->options['reduce_by_relation']))) {
      return;
    }

    if (!empty($this->options['display_depth'])) {
      $tree = $this->termStorage->loadTree($vocabulary->id(), 0, $this->options['display_depth'], TRUE);
      $terms = [];
      foreach ($tree as $term) {
        $terms[$term->id()] = $term;
      }
      $form['value']['#options'] = array_intersect_key($form['value']['#options'], $terms);
    }
    if (!empty($this->options['reduce_by_relation'])) {
       $this->reduceTermByRelation($form, $form['value']['#options']);
      return;
    }

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
    // Bundles should store the child filters which will be updated using AJAX.
    $bundles = [];
    $form['value']['#related_filter'] = [];
    foreach ($field_definitions as $field_name => $field_definition) {
      if (strpos($field_name, 'field_') !== 0 || $field_definition->getType() !== 'entity_reference' || strpos($field_definition->getSetting('handler'), 'taxonomy_term') === FALSE) {
        continue;
      }
      $bundles[$field_name] = reset($field_definition->getSetting('handler_settings')['target_bundles']);
    }
    // $request_params consists the AJAX request query parameters including (term_node_tid_depth).
    $request_params = array_merge($this->request->query->all(), $this->request->request->all());
    $filters = [];

    // Loop through all the filters configured in the current view.
    foreach ($this->view->filter ?: [] as $name => $filter) {
      if (empty($filter->options)) {
        continue;
      }
      // Ignore non-taxonomy filters and continue.
      if (strpos($filter->options['plugin_id'], 'taxonomy_index_tid') === FALSE || !in_array($filter->options['vid'], $bundles)) {
        continue;
      }
      $form['value']['#related_filter'][] = $name;
      // Add the filter name to the form attributes for AJAX.
      $form['value']['#attributes']['data-related-filter'][] = $name;
      if (empty($request_params[$name])) {
        continue;
      }
      if (is_array($request_params[$name])) {
        $filters = array_merge($filters, $request_params[$name]);
      }
      else {
        $filters[] = $request_params[$name];
      }
    }

    if (!empty($this->view->argument)) {
      $arguments = [];
      $route_term = $this->routeMatch->getParameter('taxonomy_term');
      foreach ($this->view->argument as $name => $argument) {
        if (strpos($argument->options['plugin_id'], 'taxonomy') === FALSE || empty($argument->options['validate_options']['bundles'])) {
          continue;
        }
        if (empty($argument->options['default_argument_type'] || ($argument->options['default_argument_type'] !== 'taxonomy_term') && $argument->options['default_argument_type'] !== 'taxonomy_tid')) {
          continue;
        }
        $idx = array_search($name, array_keys($this->view->argument));
        if (!empty($this->view->args[$idx])) {
          $arguments[] = $this->view->args[$idx];
          continue;
        }
        if (!empty($route_term) && in_array($route_term->bundle(), $argument->options['validate_options']['bundles']) && !in_array($route_term->id(), $arguments)) {
          $arguments[] = $route_term->id();
        }
      }

      foreach ($arguments as $tid) {
        $arg_term = Term::load($tid);
        if (empty($arg_term)) {
          continue;
        }
        if (in_array($arg_term->bundle(), $bundles)) {
          $filters[] = $tid;
          continue;
        }
        $field_definitions = $this->entityFieldManager->getFieldDefinitions('taxonomy_term', $arg_term->bundle());
        foreach ($field_definitions as $field_name => $field_definition) {
          if (strpos($field_name, 'field_') !== 0 || $field_definition->getType() !== 'entity_reference' || strpos($field_definition->getSetting('handler'), 'taxonomy_term') === FALSE) {
            continue;
          }
          $target_bundle = reset($field_definition->getSetting('handler_settings')['target_bundles']);
          if (!in_array($target_bundle, $bundles)) {
            continue;
          }
          $field_value = $arg_term->get($field_name)->getValue();
          if (empty($field_value[0]['target_id'])) {
            continue;
          }
          $filters[] = $field_value[0]['target_id'];
        }
      }
    }

    // If no parent filter is selected display no child filters.
    if (empty($filters)) {
      $form['value']['#options'] = [];
      if (!empty($this->options['hide_if_empty_options'])) {
        if (empty($this->options['reduce_by_relation'])) {
          $form['value']['#access'] = FALSE;
        }
        $form['value']['#wrapper_attributes']['class'][] = 'hidden';
      }
      return;
    }
    foreach ($options as $key => $option) {
      $tid = is_array($option) ? key($option) : $key;
      $term = $this->termStorage->load($tid);
      foreach ($bundles as $field_name => $bundle) {
        $field = $term->get($field_name);
        // If the field doesn't contain any reference, unset the option.
        if (empty($field)) {
          unset($options[$key]);
        }

        $target_ids = $field->getValue();
        foreach ($target_ids as $target_id) {
          if (in_array($target_id['target_id'], $filters)) {
            continue 3;
          }
        }
      }
      unset($options[$key]);
    }
    // Return the filtered options.
    $form['value']['#options'] = $options;
    if (empty($options)) {
      if (empty($this->options['reduce_by_relation'])) {
        $form['value']['#access'] = FALSE;
      }
      $form['value']['#wrapper_attributes']['class'][] = 'hidden';
    }
  }

}
