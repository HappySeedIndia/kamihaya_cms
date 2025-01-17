<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Fetcher\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;

/**
 * Provides a form on the feed edit page for the ContentservApiFetcher.
 */
class ContentservApiDataFetcherFeedForm extends ExternalPluginFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $form['source'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Data ID'),
      '#description' => $this->t('The ID of the data to fetch.'),
      '#default_value' => $feed->getSource(),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $feed->setSource($form_state->getValue('source'));
  }

}
