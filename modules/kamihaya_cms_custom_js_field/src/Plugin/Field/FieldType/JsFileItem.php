<?php

namespace Drupal\kamihaya_cms_custom_js_field\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Plugin implementation of the 'js_file' field type.
 */
#[FieldType(
  id: "js_file",
  label: new TranslatableMarkup("JavaScript File"),
  description: new TranslatableMarkup("Upload JavaScript files with optional TXT to JS conversion"),
  default_widget: "js_file_widget",
  default_formatter: "js_file_script_tag",
  list_class: FileFieldItemList::class,
  constraints: ["ReferenceAccess" => [], "FileValidation" => []]
)]
class JsFileItem extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'file_extensions' => 'js txt',
      'file_directory' => 'js-files/[date:custom:Y]-[date:custom:m]',
      'max_filesize' => '',
      'description_field' => FALSE,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::fieldSettingsForm($form, $form_state);
    $element['file_extensions']['#default_value'] = $this->getSetting('file_extensions');
    $element['file_directory']['#default_value'] = $this->getSetting('file_directory');

    return $element;
  }

}
