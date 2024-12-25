<?php

namespace Drupal\kamihaya_cms_views_extension\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsStyle;
use Drupal\views\Plugin\views\style\Table;
use Drupal\views_flipped_table\Plugin\views\style\FlippedTable;

/**
 * Style plugin to render each item as a column in a table.
 *
 * @ingroup views_style_plugins
 */
#[ViewsStyle(
  id: "kamihaya_flipped_table",
  title: new TranslatableMarkup("Flipped Table"),
  help: new TranslatableMarkup("Displays a table with rows and columns flipped."),
  theme: "views_view_flipped_table",
  display_types: ["normal"],
)]
class KamihayaFlippedTable extends FlippedTable {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['row_class']['#access'] = TRUE;
  }
}
