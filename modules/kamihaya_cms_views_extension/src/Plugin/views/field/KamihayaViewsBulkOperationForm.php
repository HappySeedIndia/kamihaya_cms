<?php

namespace Drupal\kamihaya_cms_views_extension\Plugin\views\field;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\style\Table;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views_bulk_operations\Plugin\views\field\ViewsBulkOperationsBulkForm;

/**
 * Extend the Views Bulk Operations field plugin.
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("kamihaya_views_bulk_operation_form")]
class KamihayaViewsBulkOperationForm extends ViewsBulkOperationsBulkForm {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['hide_header'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $actions = $form['selected_actions'];
    unset($form['selected_actions']);

    $form['hide_header'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide header'),
      '#description' => $this->t('Hide the header of the bulk form.'),
      '#default_value' => $this->options['hide_header'],
    ];

    $form['selected_actions'] = $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(array &$form, FormStateInterface $form_state): void {
    parent::viewsForm($form, $form_state);

    if ($this->options['hide_header']) {
      $form['header'][$this->options['id']]['#access'] = FALSE;
      if (!empty($form['header'][$this->options['id']]['actions'])) {
        $form['header']['actions'] = $form['header'][$this->options['id']]['actions'];
      }
    }
  }

}
