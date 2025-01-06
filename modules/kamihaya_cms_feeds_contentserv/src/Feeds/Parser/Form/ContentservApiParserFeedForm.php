<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Parser\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;

/**
 * Provides a form on the feed edit page for the ContentservParser.
 */
class ContentservApiParserFeedForm extends ExternalPluginFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $feed_config = $feed->getConfigurationFor($this->plugin);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $feed->setConfigurationFor($this->plugin, $form_state->getValues());
  }

}
