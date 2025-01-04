<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\CustomSource;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Feeds\CustomSource\BlankSource;

/**
 * A JSON source.
 *
 * @FeedsCustomSource(
 *   id = "kamihaya_json",
 *   title = @Translation("Kamihaya JSON source"),
 * )
 */
class JsonSource extends BlankSource {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Add description.
    $form['value']['#description'] = $this->configSourceDescription();
    return $form;
  }

  /**
   * Returns the description for a single source.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   A translated string if there's a description. Null otherwise.
   */
  protected function configSourceDescription() {
    return $this->t("Enter the exact JSON keys hierarchy seperated by ':'. In the array value, set the conditon and value of key in this format: 'condition_key=condition_value|value_key'");
  }

}
