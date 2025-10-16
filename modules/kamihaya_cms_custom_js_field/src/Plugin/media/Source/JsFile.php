<?php

namespace Drupal\kamihaya_cms_custom_js_field\Plugin\media\Source;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\media\Plugin\media\Source\File;
use Drupal\media\Attribute\MediaSource;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\MediaTypeInterface;

/**
 * Media source for JavaScript files.
 */
#[MediaSource(
  id: "js_file",
  label: new TranslatableMarkup("JavaScript File"),
  description: new TranslatableMarkup("Use JavaScript files for reusable media assets."),
  allowed_field_types: ["js_file"],
  default_thumbnail_filename: "js.png",
  forms: [
     "media_library_add" => "\Drupal\media_library\Form\FileUploadForm",
  ]
)]
class JsFile extends File {

  /**
   * {@inheritdoc}
   */
  protected function getSourceFieldConstraints() {
    $constraints = parent::getSourceFieldConstraints();

    // Enforce JS file extensions
    $constraints['FileExtension'] = ['extensions' => 'js txt'];

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    // Let parent create the field
    $field = parent::createSourceField($type);

    // Customize field settings for JS files
    $field->setSetting('file_extensions', 'js txt');
    $field->setSetting('file_directory', 'js-files/[date:custom:Y]-[date:custom:m]');
    $field->setLabel($this->t('JavaScript file'));
    $field->setDescription($this->t('Upload JavaScript files. TXT files will be automatically converted to JS.'));

    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display) {
    // Let parent prepare the display first
    parent::prepareViewDisplay($type, $display);

    // Override with JS-specific formatter
    $source_field = $this->getSourceFieldDefinition($type)->getName();
    $display->setComponent($source_field, [
      'type' => 'js_file_script_tag',
      'label' => 'hidden',
      'settings' => [
        'inline' => FALSE,
        'async' => FALSE,
        'defer' => FALSE,
      ],
    ]);
  }

}
