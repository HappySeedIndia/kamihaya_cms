<?php

namespace Drupal\kamihaya_cms_views_extension\Trait;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Plugin\views\argument\Taxonomy;

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
    $options['extra_vids'] = ['default' => []];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildExtraOptionsForm($form, $form_state);

    if (!empty($form['vid'])) {
      $vid_form = [
        'vid' => $form['vid'],
      ];
      $vid_form['extra_vids'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Additional vocabularies'),
        '#default_value' => $this->options['extra_vids'],
        '#options' => $form['vid']['#options'],
        '#description' => $this->t('Select additional vocabularies to filter by.<br/><strong>This option does not apply to the "Simple hierarchical select" type or not selected entity bundle filter.</strong>'),
      ];
      unset($form['vid']);
    }

    $form = array_merge($vid_form, $form);

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
    // Add an option to check the default value and disable it.
    $form['check_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default selected and disabled'),
      '#default_value' => $this->options['check_disabled'] ?? FALSE,
      '#description' => $this->t('Select the options by default with the value of contextual taxonomy term and disable them.'),
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
    // Call the parent method to build the default value form.
    parent::valueForm($form, $form_state);

    // If the filter type is 'shs', no further customization is needed.
    if ($this->options['type'] === 'shs') {
      return;
    }

    // Load additional vocabularies specified in 'extra_vids', excluding the main 'vid'.
    $vocabularies = [];
    foreach ($this->options['extra_vids'] as $vid) {
      if (empty($vid) || $vid === $this->options['vid']) {
        continue;
      }
      $vocabulary = $this->vocabularyStorage->load($vid);
      if (empty($vocabulary)) {
        continue;
      }
      $vocabularies[$vid] = $vocabulary;
    }
    $vids = array_keys($vocabularies);

    // If the filter is a textfield and there are extra vocabularies, set the title and selection bundles.
    if ($this->options['type'] === 'textfield' && !empty($vocabularies)) {
      $labels = array_map(function ($vocabulary) {
          return $vocabulary->label();
      }, $vocabularies);

      $form['value']['#title'] = $this->options['limit']
        ? $this->formatPlural(
          count($labels),
          'Select terms from vocabulary @voc',
          'Select terms from vocabularies @voc',
          ['@voc' => implode(', ', $labels)]
        )
        : $this->t('Select terms');

      if ($this->options['limit']) {
        $form['value']['#selection_settings']['target_bundles'] = array_merge([$this->options['vid']], $vids);
      }
      return;
    }

    // If the filter is not a select box or is not exposed, skip further processing.
    if (($this->options['type'] !== 'select') || !$form_state->get('exposed')) {
      return;
    }

    // Limit options based on display depth if configured.
    if (!empty($this->options['display_depth'])) {
      $vocabulary = $this->vocabularyStorage->load($this->options['vid']);
      $tree = $this->termStorage->loadTree($vocabulary->id(), 0, $this->options['display_depth'], TRUE);
      $terms = [];
      foreach ($tree as $term) {
        $terms[$term->id()] = $term;
      }
      $form['value']['#options'] = array_intersect_key($form['value']['#options'], $terms);
    }

    // Process additional vocabularies for select filters.
    if (count($vocabularies) > 0) {
      // If hierarchy and limit are enabled, add hierarchical options.
      if (!empty($this->options['hierarchy']) && $this->options['limit']) {
        foreach ($vocabularies as $vocabulary) {
          $depth = !empty($this->options['display_depth']) ? $this->options['display_depth'] : NULL;
          $tree = $this->termStorage->loadTree($vocabulary->id(), 0, $depth, TRUE);
          if (empty($tree)) continue;

          foreach ($tree as $term) {
            // Skip unpublished terms unless user has admin permission.
            if (!$term->isPublished() && !$this->currentUser->hasPermission('administer taxonomy')) {
              continue;
            }
            // Build hierarchical option.
            $choice = new \stdClass();
            $choice->option = [$term->id() => str_repeat('-', $term->depth) . \Drupal::service('entity.repository')->getTranslationFromContext($term)->label()];
            $form['value']['#options'][] = $choice;
          }
        }
      }
      // Otherwise, load all terms with optional depth consideration.
      else {
        if ($this->options['limit']) {
          $options_with_depth = $form['value']['#options'];
          foreach ($vocabularies as $vocabulary) {
            if (empty($vocabulary->id())) continue;

            $query = \Drupal::entityQuery('taxonomy_term')
              ->accessCheck(TRUE)
              ->condition('vid', $vocabulary->id())
              ->sort('weight')
              ->sort('name')
              ->addTag('taxonomy_term_access');

            if (!$this->currentUser->hasPermission('administer taxonomy')) {
              $query->condition('status', 1);
            }

            $terms = Term::loadMultiple($query->execute());
            foreach ($terms as $term) {
              $form['value']['#options'][$term->id()] = \Drupal::service('entity.repository')->getTranslationFromContext($term)->label();
            }

            if (empty($this->options['display_depth'])) continue;

            $tree = $this->termStorage->loadTree($vocabulary->id(), 0, $this->options['display_depth'], TRUE);
            if (empty($tree)) continue;

            foreach ($tree as $term) {
              if (!$term->isPublished() && !$this->currentUser->hasPermission('administer taxonomy')) continue;
              $options_with_depth[$term->id()] = \Drupal::service('entity.repository')->getTranslationFromContext($term)->label();
            }
          }

          if (!empty($this->options['display_depth'])) {
            $form['value']['#options'] = array_intersect_key($form['value']['#options'], $options_with_depth);
          }
        }
      }
    }

    // If no options are available or depth/relation filtering is disabled, return early.
    if (empty($form['value']['#options'])
      || (empty($this->options['display_depth']) && empty($this->options['reduce_by_relation']))) {
      return;
    }

    // Hide the filter if it has no options and 'hide_if_empty_options' is enabled.
    $form['value']['#hide_if_empty_options'] = !empty($this->options['hide_if_empty_options']) ? $this->options['hide_if_empty_options'] : FALSE;
    if (!empty($this->options['hide_if_empty_options']) && empty($form['value']['#options'])) {
      if (empty($this->options['reduce_by_relation'])) {
        $form['value']['#access'] = FALSE;
        return;
      }
      $form['value']['#wrapper_attributes']['class'][] = 'hidden';
      return;
    }

    // Reduce options based on entity relationships if enabled.
    if (!empty($this->options['reduce_by_relation'])) {
      $this->reduceTermByRelation($form, $form['value']['#options']);
      if (!empty($this->options['check_disabled'])) {
        // Select default value and disable the filter if configured.
        $this->defaultSelectAndDisabled($form, $form_state);
      }
      return;
    }

    // Build hierarchical options for the main vocabulary tree.
    if (empty($tree)) return;

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

    if (empty($options)) return;

    $form['value']['#options'] = $options;

    // Select default value and disable the filter if configured.
    if (!empty($this->options['check_disabled'])) {
      $this->defaultSelectAndDisabled($form, $form_state);
    }
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
    $vids = array_merge([$this->options['vid']], $this->options['extra_vids']);
    $bundles = [];
    $form['value']['#related_filter'] = [];
    foreach ($vids as $vid) {
      if (empty($vid)) {
        continue;
      }
      $field_definitions = $this->entityFieldManager->getFieldDefinitions('taxonomy_term', $vid);
      // Bundles should store the child filters which will be updated using AJAX.
      foreach ($field_definitions as $field_name => $field_definition) {
        if (strpos($field_name, 'field_') !== 0 || $field_definition->getType() !== 'entity_reference' || strpos($field_definition->getSetting('handler'), 'taxonomy_term') === FALSE) {
          continue;
        }

        $bundles[$field_name] = reset($field_definition->getSetting('handler_settings')['target_bundles']);
      }
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
      if (strpos($filter->options['plugin_id'], 'taxonomy_index_tid') === FALSE || (!in_array($filter->options['vid'], $bundles) && empty(array_intersect($bundles, $vids)))) {
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
      if (empty($term)) {
        unset($options[$key]);
        continue;
      }
      foreach ($bundles as $field_name => $bundle) {
        if (!$term->hasField($field_name)) {
          unset($options[$key]);
          continue;
        }
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

  /**
   * {@inheritdoc}
   */
  protected function opHelper() {
    // Call the parent operation helper to preserve base functionality.
    parent::opHelper();

    // Exit early if there is no value, the operator is not 'OR',
    // or required vocabulary IDs are missing.
    if (empty($this->value) || $this->operator !== 'or' || empty($this->options['extra_vids']) || empty($this->options['vid'])) {
      return;
    }

    // Prepare an array to store extra vocabularies to process.
    $vids = [];
  foreach ($this->options['extra_vids'] as $vid) {
      if (empty($vid) || $vid === $this->options['vid']) {
        continue;
      }
      $vids[$vid] = [];
    }
    if (empty($vids)) {
      return;
    }

    // Exit if the view filter or its type is not available.
    if (empty($this->view->filter) || empty($this->view->filter['type'])) {
      return;
    }

    // Get the entity type and bundles for the main filter.
    $type_filter = $this->view->filter['type'];
    $entity_type = $type_filter->getEntityType();
    $bundles = $type_filter->value;
    if (empty($bundles) || !is_array($bundles)) {
      return;
    }

    // Get the tables currently in the query to match field tables.
    $tables = $this->query->getTableQueue();
    if (empty($tables) || !is_array($tables)) {
      return;
    }

    // Iterate over bundles to find entity reference fields referencing taxonomy terms.
    foreach ($bundles as $bundle) {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);

      // Only consider fields starting with 'field_', of type 'entity_reference',
      // and referencing taxonomy terms.
      foreach ($field_definitions as $field_name => $field_definition) {
        if (strpos($field_name, 'field_') !== 0
          || $field_definition->getType() !== 'entity_reference'
          || strpos($field_definition->getSetting('handler'), 'taxonomy_term') === FALSE) {
          continue;
        }

        $handler_settings = $field_definition->getSetting('handler_settings');
        if (empty($handler_settings['target_bundles']) || empty(array_intersect_key($handler_settings['target_bundles'], $vids))) {
          continue;
        }

        // For each target bundle, map the field to the correct table and column.
        foreach (array_intersect_key($handler_settings['target_bundles'], $vids) as $target_bundle) {
          if (!isset($vids[$target_bundle])) {
            continue;
          }

          $storage = $this->entityTypeManager->getStorage($entity_type);
          $table_mapping = $storage->getTableMapping();

          // Determine the actual table and column names for the field.
          $data_table = $table_mapping->getFieldTableName($field_name);
          $columns = $table_mapping->getColumnNames($field_name);
          if (empty($columns) || empty($columns['target_id'])) {
            continue;
          }

          // Match the table alias used in the query.
          foreach ($tables as $alias => $table) {
            if ($data_table === $alias) {
              break;
            }
            if ($table['table'] === $data_table) {
              $data_table = $alias;
              break;
            }
          }

          // Store the fully-qualified field names for adding OR conditions.
          $vids[$target_bundle][] = "$data_table.{$columns['target_id']}";
        }
      }
    }

    // Prepare to replace the original filter condition with OR logic.
    $default_field = $this->helper->getField();
    $where = &$this->query->where;
    $field_conditution = [];
    $max_index = 0;

    // Find and remove existing conditions related to the original filter field.
    foreach ($where as $group_idx => $group) {
      if ($max_index < $group_idx) {
        $max_index = $group_idx;
      }
      if (empty($group['conditions'])) {
        continue;
      }
      foreach ($group['conditions'] as $idx => $condition) {
        if (empty($condition['field']) || strpos($condition['field'], $default_field) === FALSE) {
          continue;
        }
        $field_conditution = $condition;
        unset($where[$group_idx]['conditions'][$idx]);
      }
    }
    if (empty($field_conditution)) {
      return;
    }

    // Add the original filter field as the first condition in a new OR group.
    $max_index++;
    $this->query->addWhere($max_index, $default_field, $this->value, 'IN');
    $this->query->setWhereGroup('OR', $max_index);

    // Add all extra taxonomy reference fields to the same OR group.
    foreach ($vids as $vid => $fields) {
      if (empty($fields)) {
        continue;
      }
      foreach ($fields as $field) {
        $this->query->addWhere($max_index, $field, $this->value, 'IN');
      }
    }
  }


  /**
   * Select the default value and disable it.
   *
   * @param array $form
   *   The form array.
   */
  protected function defaultSelectAndDisabled(array &$form, FormStateInterface $form_state) {
    if (empty($this->options['vid']) || empty($this->options['check_disabled']) || empty($form['value']['#options']) || empty($this->view->argument)) {
      return;
    }

    $user_input = $form_state->getUserInput();
    foreach($this->view->argument as $name => $argument) {
      if (! ($argument instanceof Taxonomy)) {
        continue;
      }
      $value = $argument->getValue();
      if (empty($value) || !array_key_exists($value, $form['value']['#options'])) {
        continue;
      }
      $user_input[$this->options['expose']['identifier']] = [$value => $value];
      $form_state->setUserInput($user_input);
    }
    $form['value']['#attributes']['disabled'] = 'disabled';
  }
}
