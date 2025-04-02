<?php

declare(strict_types=1);

namespace Drupal\kamihaya_cms_views_extension\Plugin\views\field;

use Drupal\Component\Render\MarkupInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Provides Quantity field handler.
 *
 * @ViewsField("kamihaya_cms_views_extension_quantity")
 */
final class KamihayaQuantityTotal extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    // For non-existent columns (i.e. computed fields) this method must be
    // empty.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values): string|MarkupInterface {
    $build['#cache']['max-age'] = 0;
    $build['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity'),
      '#title_display' => 'invisible',
      // @todo check why default_value doesn't work.
      '#value' => 1,
      '#min' => 1,
      '#max' => 99,
      '#attributes' => [
        'class' => ['js-kamihaya-cms-quantity'],
        'autocomplete' => 'off',
      ],
      '#attached' => [
        'library' => ['kamihaya_cms_views_extension/estimate'],
      ],
    ];
    return \Drupal::service('renderer')->render($build);
  }

}
