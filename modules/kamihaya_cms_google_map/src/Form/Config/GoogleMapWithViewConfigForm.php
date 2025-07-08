<?php

namespace Drupal\kamihaya_cms_google_map\Form\Config;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Google Map with View pages.
 */
class GoogleMapWithViewConfigForm extends ConfigFormBase {

  /**
   * Constructs a new RouteProvider object.
   *
   * @param \Drupal\Core\Routing\RouteBuilderInterface $routeBuilder
   *   The route builder service.
   */
  public function __construct(protected RouteBuilderInterface $routeBuilder) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['kamihaya_cms_google_map.google_map_with_view.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kamihaya_cms_google_map_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('kamihaya_cms_google_map.google_map_with_view.settings');
    $pages = $config->get('pages') ?? [];

    $form['pages'] = [
      '#type' => 'container',
      '#title' => $this->t('Google Map with View Pages'),
      '#tree' => TRUE,
    ];

    // Display existing pages if available.
    if (!empty($pages) && is_array($pages)) {
      $form['pages']['existing_pages_info'] = [
        '#type' => 'item',
        '#title' => $this->t('Existing Pages'),
        '#description' => $this->t('You have @count existing page(s). Each page is identified by its configuration name (machine name).', [
          '@count' => count($pages),
        ]),
      ];

      // Add each page configuration form.
      ksort($pages);
      foreach ($pages as $key => $page) {
        $form['pages']['existing_pages_info'][$key] = $this->buildPageForm($page, $key);
      }
    } else {
      $form['pages']['no_pages_info'] = [
        '#type' => 'item',
        '#title' => $this->t('No Pages'),
        '#description' => $this->t('No pages have been configured yet. Use the form below to add your first page.'),
      ];
    }

    // Add new page configuration form.
    $form['pages']['new_page'] = [
      '#type' => 'details',
      '#title' => $this->t('Add New Page'),
      '#open' => empty($pages),
      '#tree' => TRUE,
    ];

    $form['pages']['new_page']['config_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Configuration Name'),
      '#description' => $this->t('Machine name for this configuration (used in URL). Only lowercase letters, numbers, and underscores are allowed.'),
      '#pattern' => '[a-z0-9_]+',
      '#required' => FALSE,
      '#default_value' => '',
    ];

    // Add form elements for the new page.
    $view_blocks = $this->getViewBlocks();

    $form['pages']['new_page']['page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page Title'),
      '#default_value' => '',
      '#required' => FALSE,
    ];

    $form['pages']['new_page']['url_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL Path'),
      '#default_value' => '',
      '#description' => $this->t('Path for the page (e.g., google-map/locations)'),
      '#required' => FALSE,
    ];

    $form['pages']['new_page']['view_block'] = [
      '#type' => 'select',
      '#title' => $this->t('View Block'),
      '#options' => ['' => $this->t('- Select -')] + $view_blocks,
      '#default_value' => '',
      '#required' => FALSE,
      '#description' => $this->t('Select a view block display (format: view_name-display_id)'),
    ];

    $form['pages']['new_page']['pc_position'] = [
      '#type' => 'select',
      '#title' => $this->t('PC Map Position'),
      '#options' => [
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => 'top',
    ];

    $form['pages']['new_page']['sp_position'] = [
      '#type' => 'select',
      '#title' => $this->t('SP Map Position'),
      '#options' => [
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
      ],
      '#default_value' => 'top',
    ];

    $form['add_page'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Page'),
      '#submit' => ['::addPage'],
      '#validate' => ['::validateAddPage'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validation handler for adding a new page.
   */
  public function validateAddPage(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $new_page = $values['pages']['new_page'] ?? [];
    $config_name = $new_page['config_name'] ?? '';

    // Check if config_name is empty.
    if (empty($config_name)) {
      $form_state->setErrorByName('pages][new_page][config_name', $this->t('Configuration Name is required when adding a new page.'));
      return;
    }

    // Check if page_title is empty.
    if (empty($new_page['page_title'])) {
      $form_state->setErrorByName('pages][new_page][page_title', $this->t('Page Title is required when adding a new page.'));
    }

    // Check if url_path is empty.
    if (empty($new_page['url_path'])) {
      $form_state->setErrorByName('pages][new_page][url_path', $this->t('URL Path is required when adding a new page.'));
    }

    // Check if view_block is empty.
    if (empty($new_page['view_block'])) {
      $form_state->setErrorByName('pages][new_page][view_block', $this->t('View Block is required when adding a new page.'));
    }

    // Check if config_name contains only valid characters.
    if (!preg_match('/^[a-z0-9_]+$/', $config_name)) {
      $form_state->setErrorByName('pages][new_page][config_name', $this->t('Configuration Name must contain only lowercase letters, numbers, and underscores.'));
      return;
    }

    // Check if the configuration name already exists.
    $config = $this->config('kamihaya_cms_google_map.google_map_with_view.settings');
    $existing_pages = $config->get('pages') ?? [];

    if (isset($existing_pages[$config_name])) {
      $form_state->setErrorByName('pages][new_page][config_name', $this->t('Configuration Name "@name" already exists. Please choose a different name.', ['@name' => $config_name]));
    }
  }

  /**
   * Build form elements for a single page configuration.
   */
  private function buildPageForm($page = [], $key = 'new') {
    $view_blocks = $this->getViewBlocks();

    // Control the title based on whether it's a new page or an existing one.
    $title = '';
    if ($key === 'new') {
      $title = $this->t('New Page');
    } else {
      $page_title = $page['page_title'] ?? '';
      $title = $this->t('Page: @config_name (@page_title)', [
        '@config_name' => $key,
        '@page_title' => $page_title ?: $this->t('No title'),
      ]);
    }

    $form = [
      '#type' => 'details',
      '#title' => $title,
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    // If this is not a new page, display the configuration name.
    if ($key !== 'new') {
      $form['config_name_display'] = [
        '#type' => 'item',
        '#title' => $this->t('Configuration Name'),
        '#markup' => '<strong>' . $key . '</strong>',
        '#description' => $this->t('This is the machine name for this configuration (used in URL)'),
      ];
    }

    $form['page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page Title'),
      '#default_value' => $page['page_title'] ?? '',
      '#required' => TRUE,
    ];

    $form['url_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL Path'),
      '#default_value' => $page['url_path'] ?? '',
      '#description' => $this->t('Path for the page (e.g., google-map/locations)'),
      '#required' => TRUE,
    ];

    $form['view_block'] = [
      '#type' => 'select',
      '#title' => $this->t('View Block'),
      '#options' => $view_blocks,
      '#default_value' => $page['view_block'] ?? '',
      '#required' => TRUE,
      '#description' => $this->t('Select a view block display (format: view_name-display_id)'),
    ];

    $form['pc_position'] = [
      '#type' => 'select',
      '#title' => $this->t('PC Map Position'),
      '#options' => [
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => $page['pc_position'] ?? 'top',
    ];

    $form['sp_position'] = [
      '#type' => 'select',
      '#title' => $this->t('SP Map Position'),
      '#options' => [
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
      ],
      '#default_value' => $page['sp_position'] ?? 'top',
    ];

    if ($key !== 'new') {
      $form['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#submit' => ['::removePage'],
        '#name' => 'remove_' . $key,
        '#attributes' => [
          'class' => ['button', 'button--danger'],
          'onclick' => 'return confirm("' . $this->t('Are you sure you want to remove this page configuration?') . '");',
        ],
      ];
    }

    return $form;
  }

  /**
   * Submit handler for adding a new page.
   */
  public function addPage(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('kamihaya_cms_google_map.google_map_with_view.settings');
    $pages = $config->get('pages') ?? [];

    // Initialize pages as an empty array if not already set.
    if (!is_array($pages)) {
      $pages = [];
    }

    $values = $form_state->getValues();
    $new_page = $values['pages']['new_page'] ?? [];
    $config_name = $new_page['config_name'] ?? '';

    if (!empty($config_name)) {
      // Check if the view_block configuration already exists.
      if (!empty($new_page['view_block'])) {
        // Separate view_name and view_display from view_block.
        $parts = explode('-', $new_page['view_block'], 2);
        $new_page['view_name'] = $parts[0];
        $new_page['view_display'] = isset($parts[1]) ? $parts[1] : 'default';
      }

      // Set the configuration name as the key for the new page.
      $pages[$config_name] = $new_page;
      $config->set('pages', $pages)->save();

      // Create or update the custom page configuration.
      $page_config = $this->configFactory->getEditable('kamihaya_cms_google_map.google_map_with_view.page.' . $config_name);
      $page_config->setData($new_page)->save();

      // Rebuild the routes to reflect the new or updated pages.
      $this->routeBuilder->rebuild();

      $this->messenger()->addMessage($this->t('Page "@name" has been added.', ['@name' => $new_page['page_title']]));
    }

  }

  /**
   * Submit handler for removing a page.
   */
  public function removePage(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $key = str_replace('remove_', '', $trigger['#name']);

    $config = $this->configFactory->getEditable('kamihaya_cms_google_map.google_map_with_view.settings');
    $pages = $config->get('pages') ?? [];

    // Initialize pages as an empty array if not already set.
    if (!is_array($pages)) {
      $pages = [];
    }

    if (isset($pages[$key])) {
      unset($pages[$key]);
      $config->set('pages', $pages)->save();

      // Delete the corresponding page configuration.
      $page_config = $this->configFactory->getEditable('kamihaya_cms_google_map.google_map_with_view.page.' . $key);
      $page_config->delete();

      $this->messenger()->addMessage($this->t('Page has been removed.'));
    }

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('kamihaya_cms_google_map.google_map_with_view.settings');
    $form_state->cleanValues();
    $values = $form_state->getValues();

    if (!empty($values['pages']) && !empty($values['pages']['existing_pages_info'])) {
      $values['pages'] += $values['pages']['existing_pages_info'];
      unset($values['pages']['existing_pages_info']);
    }

    // Get existing pages from configuration.
    $pages = $config->get('pages') ?? [];

    // Initialize pages as an empty array if not already set.
    if (!is_array($pages)) {
      $pages = [];
    }

    // Get the pages from the form values.
    if (isset($values['pages']) && is_array($values['pages'])) {
      foreach ($values['pages'] as $key => $page_data) {
        if ($key === 'new_page') {
          continue;
        }
        // Separate view_name and view_display from view_block.
        if (!empty($page_data['view_block'])) {
          $parts = explode('-', $page_data['view_block'], 2);
          $page_data['view_name'] = $parts[0];
          $page_data['view_display'] = isset($parts[1]) ? $parts[1] : 'default';
        }

        // Save the page configuration.
        $page_config = $this->configFactory->getEditable('kamihaya_cms_google_map.google_map_with_view.page.' . $key);
        $page_config->setData($page_data)->save();

        // Add or update the page in the pages array.
        $pages[$key] = $page_data;
      }

      // Save the updated pages configuration.
      $config->set('pages', $pages)->save();

      // Rebuild the routes to reflect the new or updated pages.
      $this->routeBuilder->rebuild();

      $this->messenger()->addMessage($this->t('The configuration has been updated.'));
    } else {
      // Add a warning if no pages data found in form values.
      $this->messenger()->addWarning($this->t('No pages data found in form values.'));
    }

    parent::submitForm($form, $form_state);
  }

    /**
   * Get View blocks options.
   */
  private function getViewBlocks() {
    $options = [];
    $views = Views::getEnabledViews();

    foreach ($views as $view) {
      $displays = $view->get('display');
      foreach ($displays as $display_id => $display) {
        // Block表示のみを対象とする
        if ($display['display_plugin'] === 'block') {
          $view_name = $view->id();
          $display_title = $display['display_title'];
          $options[$view_name . '-' . $display_id] = $view->label() . ' - ' . $display_title;
        }
      }
    }

    return $options;
  }
}
