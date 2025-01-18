<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Fetcher\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * The configuration form for Contentserv API fetchers.
 */
class ContentservApiDataFetcherForm extends ContentservApiFetcherForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['limit']);
    unset($form['scheduled_execution']);
    unset($form['scheduled_minute']);
    return $form;
  }

}
