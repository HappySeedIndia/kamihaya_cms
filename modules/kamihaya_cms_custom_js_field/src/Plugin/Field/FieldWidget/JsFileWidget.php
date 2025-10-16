<?php

namespace Drupal\kamihaya_cms_custom_js_field\Plugin\Field\FieldWidget;

use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'js_file_widget' widget.
 */
#[FieldWidget(
  id: "js_file_widget",
  label: new TranslatableMarkup("JavaScript File Upload"),
  field_types: ["js_file"]
)]
class JsFileWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Add validation callback for TXT to JS conversion
    $element['#upload_validators']['file_validate_extensions'][0] = $this->getFieldSetting('file_extensions');

    return $element;
  }

}
