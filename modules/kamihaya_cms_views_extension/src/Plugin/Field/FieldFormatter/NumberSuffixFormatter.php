<?php

declare(strict_types=1);

namespace Drupal\kamihaya_cms_views_extension\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Prints only the suffix of the number field.
 *
 * @FieldFormatter(
 *   id = "kamihaya_cms_views_extension_number_suffix",
 *   label = @Translation("Number Suffix"),
 *   field_types = {"integer"},
 * )
 */
final class NumberSuffixFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];
    foreach ($items as $delta => $item) {
      $suffix = $item->getFieldDefinition()->getSetting('suffix') ?? '';
      $element[$delta] = [
        '#markup' => $suffix,
      ];
    }
    return $element;
  }

}
