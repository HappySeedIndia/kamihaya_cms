<?php

namespace Drupal\kamihaya_cms_views_extension\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsStyle;
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
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['table_layout'] = ['default' => 'auto'];
    $options['td_width'] = ['default' => ''];
    $options['th_width'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['row_class']['#access'] = TRUE;

    $form['table_layout'] = [
      '#type' => 'select',
      '#options' => [
        'auto' => $this->t('Auto'),
        'fixed' => $this->t('Fixed'),
      ],
      '#title' => $this->t('Table Layout'),
      '#default_value' => $this->options['table_layout'],
      '#description' => $this->t("Sets the table layout to be used for a table."),
    ];

    $form['td_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Table Data(td) Width'),
      '#default_value' => $this->options['td_width'],
      '#description' => $this->t("Sets the width of the table data(td) element. ex. 100px, 50%, 10em"),
      '#states' => [
        'visible' => [
          ':input[name="style_options[table_layout]"]' => ['value' => 'fixed'],
        ],
      ],
    ];

    $form['th_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Table Header(th) Width'),
      '#default_value' => $this->options['th_width'],
      '#description' => $this->t("Sets the width of the table header(th) element. ex. 100px, 50%, 10em"),
    ];
  }

}
