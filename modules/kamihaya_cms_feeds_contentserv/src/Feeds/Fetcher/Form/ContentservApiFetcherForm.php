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
    $form['json_api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('JSON API URL'),
      '#required' => TRUE,
      '#description' => $this->t('The URL of the JSON API to fetch.'),
      '#default_value' => $this->plugin->getConfiguration('json_api_url'),
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#required' => TRUE,
      '#description' => $this->t('The API key to use for the request.'),
      '#default_value' => $this->plugin->getConfiguration('api_key'),
    ];

    $form['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret'),
      '#required' => TRUE,
      '#description' => $this->t('The secret to use for the request.'),
      '#default_value' => $this->plugin->getConfiguration('secret'),
    ];

    $form['folder_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Folder ID'),
      '#description' => $this->t('The folder ID to use for the request.'),
      '#default_value' => $this->plugin->getConfiguration('folder_id'),
    ];

    $form['data_type'] = [
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

    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit count'),
      '#required' => TRUE,
      '#description' => $this->t('The limit count for one request.'),
      '#default_value' => $this->plugin->getConfiguration('limit'),
    ];

    $form['request_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Request timeout'),
      '#description' => $this->t('Timeout in seconds to wait for an HTTP request to finish.'),
      '#default_value' => $this->plugin->getConfiguration('request_timeout'),
      '#min' => 0,
    ];

    return $form;
  }

}
