<?php

namespace Drupal\kamihaya_cms_views_extension\Plugin\views\exposed_form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\better_exposed_filters\Plugin\BetterExposedFiltersWidgetManager;
use Drupal\better_exposed_filters\Plugin\views\exposed_form\BetterExposedFilters;
use Drupal\views\Attribute\ViewsExposedForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extend Better Exposed Filters.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsExposedForm(
  id: 'kamihaya_exposed_form',
  title: new TranslatableMarkup('Kamihaya Exposed Form'),
)]
class KamihayaExposedForm extends BetterExposedFilters {

  /**
   * KamihayaExposedForm constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\better_exposed_filters\Plugin\BetterExposedFiltersWidgetManager $filterWidgetManager
   *   The better exposed filter widget manager for filter widgets.
   * @param \Drupal\better_exposed_filters\Plugin\BetterExposedFiltersWidgetManager $pagerWidgetManager
   *   The better exposed filter widget manager for pager widgets.
   * @param \Drupal\better_exposed_filters\Plugin\BetterExposedFiltersWidgetManager $sortWidgetManager
   *   The better exposed filter widget manager for sort widgets.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Manage drupal modules.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $elementInfo
   *   The element info manager.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request object.
   *
   * @patam \Drupal\Core\Routing\RouteMatchInterface
   *  The currently active route match object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected BetterExposedFiltersWidgetManager $filterWidgetManager,
    protected BetterExposedFiltersWidgetManager $pagerWidgetManager,
    protected BetterExposedFiltersWidgetManager $sortWidgetManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected ElementInfoManagerInterface $elementInfo,
    protected Request $request,
    protected RouteMatchInterface $routeMatch,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $filterWidgetManager, $pagerWidgetManager, $sortWidgetManager, $moduleHandler, $elementInfo, $request);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    // @phpstan-ignore-next-line
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.better_exposed_filters_filter_widget'),
      $container->get('plugin.manager.better_exposed_filters_pager_widget'),
      $container->get('plugin.manager.better_exposed_filters_sort_widget'),
      $container->get('module_handler'),
      $container->get('element_info'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['bef']['general']['submit_icon'] = ['default' => FALSE];
    $options['bef']['general']['hide_secondary'] = ['default' => FALSE];
    $options['bef']['general']['filter_grouping'] = ['default' => FALSE];
    $options['bef']['general']['filter_grouping_class'] = ['default' => ''];
    $options['bef']['general']['hide_filter_group'] = ['default' => FALSE];
    $options['bef']['sort']['optimize_sort_order'] = ['default' => FALSE];
    $options['bef']['sort']['hide'] = ['default' => FALSE];
    $options['bef']['sort']['label_inline'] = ['default' => FALSE];
    return $options;
  }

  /**
   * Build the views options form and adds custom options for BEF.
   *
   * @inheritDoc
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);
    $form['bef']['general'] = array_merge([
      'submit_icon' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Display submit icon'),
        '#description' => $this->t('Will display the icon instead of the text on the submit button.'),
        '#default_value' => $this->options['bef']['general']['submit_icon'],
        '#states' => [
          'invisible' => [
            'input[name="exposed_form_options[bef][general][autosubmit_hide]"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ], $form['bef']['general']);

    $form['bef']['general']['submit_button']['#states'] = [
      'invisible' => [
        'input[name="exposed_form_options[bef][general][submit_icon]"]' => ['checked' => TRUE],
      ],
    ];

    $form['bef']['general']['hide_secondary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide seconary option'),
      '#description' => $this->t('If enabled, the seconary option will be hidden on not view pages.'),
      '#default_value' => $this->options['bef']['general']['hide_secondary'],
    ];

    $form['bef']['general']['filter_grouping'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter grouping'),
      '#description' => $this->t('If enabled, the seleeted filters will be grouped.'),
      '#default_value' => $this->options['bef']['general']['filter_grouping'],
    ];

    $form['bef']['general']['filter_grouping_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter grouping css class'),
      '#description' => $this->t('This class will be added to the filter grouping container.'),
      '#default_value' => $this->options['bef']['general']['filter_grouping_class'],
      '#states' => [
        'visible' => [
          'input[name="exposed_form_options[bef][general][filter_grouping]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['bef']['general']['hide_filter_group'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide filter group'),
      '#description' => $this->t('If enabled, the filter group will be hidden on not view pages.'),
      '#default_value' => $this->options['bef']['general']['hide_filter_group'],
      '#states' => [
        'visible' => [
          'input[name="exposed_form_options[bef][general][filter_grouping]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    if (!empty($form['bef']['sort']['configuration'])) {
      $advanced = [];
      if (!empty($form['bef']['sort']['configuration']['advanced'])) {
        $advanced = $form['bef']['sort']['configuration']['advanced'];
        unset($form['bef']['sort']['configuration']['advanced']);
      }
      $form['bef']['sort']['configuration']['optimize_sort_order'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Optimize sort order'),
        '#description' => $this->t('If enabled, the sort order of selected exposed fields will be the first order. If default is selected, exposed fields will be ignored.'),
        '#default_value' => $this->options['bef']['sort']['optimize_sort_order'],
      ];
      $form['bef']['sort']['configuration']['hide'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Hide sort order'),
        '#description' => $this->t('If enabled, the exposed sort will be hidden on not view pages.'),
        '#default_value' => $this->options['bef']['sort']['hide'],
      ];
      $form['bef']['sort']['configuration']['label_inline'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Inline label'),
        '#description' => $this->t('If enabled, the label will be displayed inline.'),
        '#default_value' => $this->options['bef']['sort']['label_inline'],
      ];
      if (!empty($advanced)) {
        $form['bef']['sort']['configuration']['advanced'] = $advanced;
      }
    }

    foreach ($form['bef']['filter'] as $key => &$filter) {
      if (empty($filter['configuration']['advanced'])) {
        continue;
      }
      $filter['configuration']['advanced']['hide'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Hide filter'),
        '#description' => $this->t('If enabled, the filter will be hidden on not view pages.'),
        '#default_value' => $this->options['bef']['filter'][$key]['advanced']['hide'] ?? FALSE,
      ];

      $filter['configuration']['advanced']['include_group'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Include group'),
        '#description' => $this->t('If enabled, the filter will be included in the group.'),
        '#default_value' => $this->options['bef']['filter'][$key]['advanced']['include_group'] ?? FALSE,
        '#states' => [
          'visible' => [
            'input[name="exposed_form_options[bef][general][filter_grouping]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $index = 0;
      foreach (array_keys($filter['configuration']['advanced']) as $idx => $name) {
        if (!is_array($filter['configuration']['advanced'][$name])/* || empty($filter['configuration']['advanced'][$name]['#type'])*/) {
          continue;
        }
        $filter['configuration']['advanced'][$name]['#weight'] = $index;
        if ($name === 'hide_label') {
          $filter['configuration']['advanced']['label_inline'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Inline label'),
            '#description' => $this->t('If enabled, the label will be displayed inline.'),
            '#default_value' => $this->options['bef']['filter'][$key]['advanced']['label_inline'] ?? FALSE,
            '#states' => [
              'invisible' => [
                'input[name="exposed_form_options[bef][filter][' . $key . '][configuration][advanced][hide_label]"]' => ['checked' => TRUE],
              ],
            ],
            '#weight' => ++$index,
          ];
          $index++;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state): void {
    $options = &$form_state->getValue('exposed_form_options');
    $optimize_sort_order = $options['bef']['sort']['configuration']['optimize_sort_order'] ?? FALSE;
    $hide_sort = $options['bef']['sort']['configuration']['hide'] ?? FALSE;
    $label_inline = $options['bef']['sort']['configuration']['label_inline'] ?? FALSE;
    parent::submitOptionsForm($form, $form_state);
    if (!empty($options['bef']['sort'])) {
      $options['bef']['sort']['optimize_sort_order'] = $optimize_sort_order;
      $options['bef']['sort']['hide'] = $hide_sort;
      $options['bef']['sort']['label_inline'] = $label_inline;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(&$form, FormStateInterface $form_state): void {
    parent::exposedFormAlter($form, $form_state);
    if (!empty($form['secondary'])) {
      // Open the secondary option if the filter is selected.
      $request_params = array_merge($this->request->query->all(), $this->request->request->all());
      foreach($request_params as $key => $value) {
        if ($key === 'sort_by') {
          continue;
        }
        if (empty($form[$key]) || empty($form[$key]['#group'])) {
          continue;
        }
        $group = $form[$key]['#group'];
        if (empty($form[$group]) || empty($form[$group]['#group']) || $form[$group]['#group'] !== 'secondary') {
          continue;
        }
        $form['secondary']['#open'] = TRUE;
      }
    }

    foreach ($form as $key => &$field) {
      if (!is_array($field) || empty($field['#type'])) {
        continue;
      }
      if (!empty($this->options['bef']['filter'][$key]) && !empty($this->options['bef']['filter'][$key]['advanced']['label_inline'])) {
        if (empty($field['#attributes'])) {
          $field['#attributes'] = [];
        }
        if (empty($field['#attributes']['class'])) {
          $field['#attributes']['class'] = [];
        }
        $field['#attributes']['class'][] = 'label-inline';
      }
      if ($field['#type'] !== 'textfield' || empty($field['#attributes']['minlength'])) {
        continue;
      }
      if (empty($form['#attached']['drupalSettings']['exposed_form'])) {
        $form['#attached']['drupalSettings']['exposed_form'] = [];
        $form['#attached']['library'][] = 'kamihaya_cms_views_extension/exposed-form';
      }
      $form['#attached']['drupalSettings']['exposed_form'][$key] = $field['#attributes']['minlength'];
    }

    if (!empty($this->options['bef']['general']['submit_icon'])) {
      $form['actions']['submit']['#value'] = '';
      if (empty($form['actions']['submit']['#attributes']['class'])) {
        $form['actions']['submit']['#attributes']['class'] = [];
      }
      if (empty($this->options['bef']['general']['autosubmit_hide'])) {
        $form['actions']['submit']['#attributes']['class'][] = 'submit-icon';
        $form['actions']['submit']['#prefix'] = '<div class="button-wrapper btn-primary">';
        $form['actions']['submit']['#suffix'] = '</div>';
      }
      if (!empty($this->options['bef']['general']['autosubmit_exclude_textfield'])
        && !empty($this->options['bef']['general']['autosubmit_exclude_textfield'])) {
        $text_fields = [];
        foreach ($form as $key => &$field) {
          if (!is_array($field) || empty($field['#type']) || $field['#type'] !== 'textfield') {
            continue;
          }
          $text_fields[$key] = $field;
        }
        if (!empty($text_fields) && count($text_fields) === 1) {
          $keys = array_keys($form);
          $values = array_values($form);
          $values[array_search(array_keys($text_fields)[0], $keys)] = [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['text-button'],
            ],
            array_keys($text_fields)[0] => array_values($text_fields)[0],
            'actions' => $form['actions'],
          ];
          $keys[array_search(array_keys($text_fields)[0], $keys)] = 'text_button';
          $form = array_combine($keys, $values);
          $form['actions']['#access'] = FALSE;
        }
        $form['sort_by']['#weight'] = 10;
      }
    }
    if (!empty($this->options['bef']['sort']['label_inline'])) {
      $form['sort_by']['#attributes']['class'][] = 'label-inline';
    }
    if (!empty($this->options['bef']['general']['filter_grouping'])) {
      $form['#attributes']['class'][] = 'kamihaya-filter-grouping';
      $form['filter_grouping'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['kamihaya-filter-grouping-container'],
        ],
      ];
      if (!empty($this->options['bef']['general']['filter_grouping_class'])) {
        $form['filter_grouping']['#attributes']['class'][] = $this->options['bef']['general']['filter_grouping_class'];
      }
      foreach ($form as $key => &$field) {
        if (!is_array($field) || empty($this->options['bef']['filter'][$key]) || empty($this->options['bef']['filter'][$key]['advanced']['include_group'])) {
          continue;
        }
        $form['filter_grouping'][$key] = $field;
        unset($form[$key]);
      }
    }
    $view = $form_state->get('view');
    $path = $this->routeMatch->getRouteName();
    if (!empty($form['sort_by']) && empty($this->request->query->get('sort_by') && array_key_exists(0, $form['sort_by']['#options']))) {
      $form['sort_by']['#value'] = 0;
    }
    if ($path === "view.{$view->id()}.{$view->current_display}" || $path === 'views.ajax') {
      return;
    }
    if (!empty($form['filter_grouping']) && !empty($this->options['bef']['general']['hide_filter_group'])) {
      $form['filter_grouping']['#access'] = FALSE;
    }
    if (!empty($form['sort_by']) && !empty($this->options['bef']['sort']['hide'])) {
      $form['sort_by']['#access'] = FALSE;
    }
    if (!empty($form['secondary']) && !empty($this->options['bef']['general']['hide_secondary'])) {
      $form['secondary']['#access'] = FALSE;
    }
    foreach ($form as $key => &$field) {
      if (!is_array($field) || empty($this->options['bef']['filter'][$key]) || empty($this->options['bef']['filter'][$key]['advanced']['hide'])) {
        continue;
      }
      $field['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $exposed_input = $this->view->getExposedInput() ?? [];
    $sort_by = $exposed_input['sort_by'] ?? NULL;
    if (empty($this->options['bef']['sort']['optimize_sort_order']) || empty($sort_by)) {
      parent::query();
      return;
    }
    if (!empty($sort_by)) {
      $view = $this->view;
      $sort_alias = NULL;
      $sort_field = NULL;
      foreach ($view->sort as $sort) {
        if (!empty($sort->options['expose']['field_identifier']) && $sort->options['expose']['field_identifier'] === $sort_by) {
          $sort_field = $sort;
          break;
        }
      }
      foreach ($view->query->fields as $key => $field) {
        if ($field['table'] === $sort_field->table && $field['field'] === $sort_field->field) {
          $sort_alias = $key;
          break;
        }
      }
      foreach ($view->query->orderby as $key => $order) {
        if ($order['field'] === $sort_alias) {
          $order = $view->query->orderby[$key];
          unset($view->query->orderby[$key]);
          break;
        }
      }
      $view->query->orderby = array_merge([$order], $view->query->orderby);
    }
  }

}
