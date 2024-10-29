<?php

namespace Drupal\kamihaya_cms_views_extension\Plugin\views\style;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsStyle;
use Drupal\views\Plugin\views\style\Table;

/**
 * Style plugin to render each item as a row in a table.
 *
 * @ingroup views_style_plugins
 */
#[ViewsStyle(
  id: "kamihaya_responsive_table",
  title: new TranslatableMarkup("Responsive Table"),
  help: new TranslatableMarkup("Displays rows in a table. And the table is responsive."),
  theme: "views_view_table",
  display_types: ["normal"],
)]
class KamihayaResponsiveTable extends Table {

}
