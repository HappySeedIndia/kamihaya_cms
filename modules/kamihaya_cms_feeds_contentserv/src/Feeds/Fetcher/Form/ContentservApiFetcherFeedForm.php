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
    $form['imported'] = [
      '#title' => $this->t('Last imported time'),
      '#type' => 'datetime',
      '#default_value' => new DrupalDateTime(date('Y-m-d H:i:s', $feed->getImportedTime())),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $feed->set('imported', strtotime($form_state->getValue('imported')->format('Y-m-d H:i:s')));
  }

}
