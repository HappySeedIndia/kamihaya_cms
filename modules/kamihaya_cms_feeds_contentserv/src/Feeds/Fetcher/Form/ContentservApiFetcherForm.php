<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Fetcher\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;

/**
 * The configuration form for Contentserv API fetchers.
 */
class ContentservApiFetcherForm extends ExternalPluginFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['api_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Contentserv API Settings'),
      '#description' => $this->t('Configure the Contentserv API settings for fetching data.'),
      '#open' => FALSE,
    ];

    $form['api_settings']['json_api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('JSON API URL'),
      '#required' => TRUE,
      '#description' => $this->t('The URL of the JSON API to fetch.'),
      '#default_value' => $this->plugin->getConfiguration('json_api_url'),
    ];

    $form['api_settings']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#required' => TRUE,
      '#description' => $this->t('The API key to use for the request.'),
      '#default_value' => $this->plugin->getConfiguration('api_key'),
    ];

    $form['api_settings']['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret'),
      '#required' => TRUE,
      '#description' => $this->t('The secret to use for the request.'),
      '#default_value' => $this->plugin->getConfiguration('secret'),
    ];

    $form['api_settings']['data_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Data type'),
      '#required' => TRUE,
      '#options' => [
        'Product' => $this->t('Product'),
        'File' => $this->t('Asset'),
      ],
      '#description' => $this->t('The data type to use for the request.'),
      '#default_value' => $this->plugin->getConfiguration('data_type'),
    ];

    $form['api_settings']['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit count'),
      '#description' => $this->t('The limit count for one request.'),
      '#default_value' => $this->plugin->getConfiguration('limit'),
    ];

    $form['api_settings']['request_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Request timeout'),
      '#description' => $this->t('Timeout in seconds to wait for an HTTP request to finish.'),
      '#default_value' => $this->plugin->getConfiguration('request_timeout'),
      '#min' => 0,
    ];

    $form['api_settings']['retry_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Retry count'),
      '#description' => $this->t('The number of times to retry the request if it fails.'),
      '#default_value' => $this->plugin->getConfiguration('retry_count'),
      '#min' => 0,
      '#max' => 10,
    ];

    $form['api_settings']['retry_delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Retry delay'),
      '#description' => $this->t('The number of seconds to wait before retrying the request.'),
      '#default_value' => $this->plugin->getConfiguration('retry_delay'),
      '#min' => 0,
      '#max' => 60,
    ];

    $form['api_filter_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Contentserv API Filter Settings'),
      '#description' => $this->t('Configure the filters for fetching data from Contentserv API.'),
      '#open' => FALSE,
    ];

    $form['api_filter_settings']['folder_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Folder ID'),
      '#description' => $this->t('The folder ID to use for the request.'),
      '#default_value' => $this->plugin->getConfiguration('folder_id'),
    ];

    $form['api_filter_settings']['filter_by_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter by Date'),
      '#description' => $this->t('Enable to filter by date.'),
      '#default_value' => $this->plugin->getConfiguration('filter_by_date', FALSE),
    ];

    $form['api_filter_settings']['check_state'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check State'),
      '#description' => $this->t('Enable to filter by state.'),
      '#default_value' => $this->plugin->getConfiguration('check_state', FALSE),
    ];

    $form['api_filter_settings']['state_ids'] = [
      '#type' => 'textfield',
      '#title' => $this->t('State IDs'),
      '#description' => $this->t('Comma-separated list of state IDs to filter by.'),
      '#default_value' => !empty($this->plugin->getConfiguration('state_ids', [])) ? implode(',', $this->plugin->getConfiguration('state_ids')): [],
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          ':input[name="fetcher_configuration[api_filter_settings][check_state]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="fetcher_configuration[api_filter_settings][check_state]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['api_filter_settings']['check_class'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check Class'),
      '#description' => $this->t('Enable to filter by class.'),
      '#default_value' => $this->plugin->getConfiguration('check_class', FALSE),
    ];

    $form['api_filter_settings']['class_ids'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class IDs'),
      '#description' => $this->t('Comma-separated list of class IDs to filter by.'),
      '#default_value' => !empty($this->plugin->getConfiguration('class_ids')) ? implode(',', $this->plugin->getConfiguration('class_ids')): [],
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          ':input[name="fetcher_configuration[api_filter_settings][check_class]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="fetcher_configuration[api_filter_settings][check_class]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['api_filter_settings']['check_class_negate_condition'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Negate Class Condition'),
      '#description' => $this->t('Negate the class condition.'),
      '#default_value' => $this->plugin->getConfiguration('check_class_negate_condition', FALSE),
      '#states' => [
        'visible' => [
          ':input[name="fetcher_configuration[api_filter_settings][check_class]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['api_filter_settings']['check_tags'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check Tags'),
      '#description' => $this->t('Enable to filter by tags.'),
      '#default_value' => $this->plugin->getConfiguration('check_tags', FALSE),
    ];

    $form['api_filter_settings']['tag_ids'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tag IDs'),
      '#description' => $this->t('Comma-separated list of tag IDs to filter by.'),
      '#default_value' => !empty($this->plugin->getConfiguration('tag_ids')) ? implode(',', $this->plugin->getConfiguration('tag_ids')): [],
      '#maxlength' => 512,
      '#states' => [
        'visible' => [
          ':input[name="fetcher_configuration[api_filter_settings][check_tags]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['api_filter_settings']['extra_filters'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Extra Filters'),
      '#description' => $this->t('Additional filters to apply in URL query format.'),
      '#default_value' => $this->plugin->getConfiguration('extra_filters', '') ?? '',
    ];

    $form['create_content'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create Content'),
      '#description' => $this->t('Crerate content regardless of the changed date.'),
      '#default_value' => $this->plugin->getConfiguration('unpublish_content', TRUE),
    ];

    $form['unpublish_content'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unpublish Content'),
      '#description' => $this->t('Unpublish content that is not found in the API response.'),
      '#default_value' => $this->plugin->getConfiguration('unpublish_content', FALSE),
    ];

    $form['scheduled_execution'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Scheduled execution'),
      '#description' => $this->t('Enable this option to execute the feed import on a schedule.'),
      '#default_value' => $this->plugin->getConfiguration('scheduled_execution'),
    ];

    $form['scheduled_minute'] = [
      '#type' => 'number',
      '#title' => $this->t('Execute minute'),
      '#max' => 59,
      '#min' => 0,
      '#description' => $this->t('Execute the feed import at the specified minute every hour.'),
      '#default_value' => $this->plugin->getConfiguration('scheduled_minute'),
      '#states' => [
        'visible' => [
          ':input[name="fetcher_configuration[scheduled_execution]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($values as $key => &$value) {
      if (!is_array($value)) {
        continue;
      }
      foreach ($value as $sub_key => $sub_value) {
        // Ensure sub-values are trimmed.
        $values[$sub_key] = trim($sub_value);
        unset($value[$sub_key]);
      }
      unset($values[$key]);
    }
    // Convert comma-separated values to arrays.
    $values['garbage_folder_ids'] = !empty($values['garbage_folder_ids']) ? array_filter(array_map('trim', explode(',', $values['garbage_folder_ids']))) : [];
    $values['state_ids'] = !empty($values['state_ids']) ? array_filter(array_map('trim', explode(',', $values['state_ids']))) : [];
    $values['class_ids'] = !empty($values['class_ids']) ? array_filter(array_map('trim', explode(',', $values['class_ids']))) : [];
    $values['tag_ids'] = !empty($values['tag_ids']) ? array_filter(array_map('trim', explode(',', $values['tag_ids']))) : [];
    $this->plugin->setConfiguration($values);
  }
}
