<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Fetcher\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;

/**
 * Provides a form on the feed edit page for the ContentservApiFetcher.
 */
class ContentservApiFetcherFeedForm extends ExternalPluginFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $feed_config = $feed->getConfigurationFor($feed->getType()->getFetcher());
    $last_import_start_time = !empty($feed_config['last_import_start_time']) ? $feed_config['last_import_start_time'] : time();
    $form['last_import_start_time'] = [
      '#title' => $this->t('Last imported time'),
      '#type' => 'datetime',
      '#default_value' => new DrupalDateTime(date('Y-m-d H:i:s', $last_import_start_time)),
    ];
    $form['source'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Data IDs'),
      '#description' => $this->t('Comma-separated IDs of the data to fetch.<br/>If this is set, ignore the Last imported time.'),
      '#default_value' => $feed->getSource(),
      '#maxlength' => 768,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $feed_config = $feed->getConfigurationFor($feed->getType()->getFetcher());
    $feed_config['last_import_start_time'] = strtotime($form_state->getValue('last_import_start_time')->format('Y-m-d H:i:s'));
    $feed->setConfigurationFor($feed->getType()->getFetcher(), $feed_config);

    // Set the source to the feed.
    $feed->setSource($form_state->getValue('source'));
  }

}
