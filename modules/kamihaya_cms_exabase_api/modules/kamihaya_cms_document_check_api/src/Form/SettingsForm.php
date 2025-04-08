<?php

declare(strict_types=1);

namespace Drupal\kamihaya_cms_document_check_api\Form;

use Drupal\kamihaya_cms_exabase_api\Form\SettingsFormBase;

/**
 * Configure ExabaseApi settings for this site.
 */
final class SettingsForm extends SettingsFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'kamihaya_cms_document_check_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['kamihaya_cms_document_check_api.settings'];
  }
}
