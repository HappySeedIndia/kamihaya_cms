<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Parser\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;

/**
 * The configuration form for the Contentserv parser.
 */
class ContentservApiParserForm extends ExternalPluginFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    return $form;
  }

}
