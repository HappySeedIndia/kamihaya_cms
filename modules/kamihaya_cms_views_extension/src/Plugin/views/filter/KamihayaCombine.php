<?php

namespace Drupal\kamihaya_cms_views_extension\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\Combine;

/**
 * Extend the combine filter.
 *
 * @ingroup views_field_handlers
 */
#[ViewsFilter("kamihaya_combine")]
class KamihayaCombine extends Combine {

  /**
   * @var \Drupal\views\Plugin\views\query\QueryPluginBase
   */
  public $query;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['expose']['contains']['min_length'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['expose']['min_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Minumum length'),
      '#description' => $this->t("Minimum length of the search string. Leave empty to disable."),
      '#default_value' => $this->options['expose']['min_length'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateExposeForm($form, FormStateInterface $form_state) {
    parent::validateExposeForm($form, $form_state);
    $min_length = $form_state->getValue(['options', 'expose', 'min_length']);

    if (!empty($min_length) && $min_length < 0) {
      $form_state->setError(
        $form['expose']['min_length'],
        $this->t('You must enter a positive number for the minimum length.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    if (empty($this->options['expose']['min_length'])) {
      return;
    }
    $form['value']['#attributes']['minlength'] = $this->options['expose']['min_length'];
  }
}
