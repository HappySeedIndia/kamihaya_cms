<?php

namespace Drupal\kamihaya_cms_views_extension\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Extend the JSON field.
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("kamihaya_json_data")]
class KamihayaJsonDataField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['render_value'] = ['default' => TRUE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['render_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Render value'),
      '#description' => $this->t('The value to render in JSON field. Define like this: <code>%attr1:%attr2=%value|%attr3}</code><br>
        <ul>
          <li>%attr1 : Atrribute name to get the child element. This might be multiple.</li>
          <li>%attr2 : Attribute name. If the %value is defined, it becomes the condition to get the value in the same array. If not render its value.</li>
          <li>%value : The value of %attr2.</li>
          <li>%attr3 : Attribute name to render its value.</li>
        </ul>', [
        '%attr1' => 'attrbutte_1',
        '%attr2' => 'attribute_2',
        '%value' => 'value',
        '%attr3' => 'attribute_3']),
      '#default_value' => $this->options['render_value'],
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    // $value = $this->getValue($row);
    // $render_value = $this->options['render_value'];
    // if (empty($render_value) || empty($value)) {
    //   return parent::render($row);;
    // }
    // $json = json_decode($value, TRUE);
    // return $this->getAttributeValue($json, $render_value);

    static $json_cache = [];

    $entity = $row->_entity;
    $field_name = $this->field;
    $entity_id = $entity->id();
    $value = $this->getValue($row);

    if (empty($field_name) || empty($value)) {
      return parent::render($row);
    }

    // Create cache key from entity ID and field name.
    $cache_key = $entity_id . ':' . $field_name;

    // Decode JSON data if not exists in cache.
    if (empty($json_cache[$cache_key])) {
      $json_cache[$cache_key] = json_decode($value, TRUE);
    }

    $json = $json_cache[$cache_key];

    if (!is_array($json)) {
      return parent::render($row);
    }

    $render_value = $this->options['render_value'];
    return $this->getAttributeValue($json, $render_value);
  }

  /**
   * Get the attribute value from JSON.
   *
   * @param array $json
   *   The JSON data.
   * @param string $render_value
   *   The render value.
   *
   * @return string
   *   The attribute value.
   */
  private function getAttributeValue(array $json, $render_value) {
    $attributes = explode(':', $render_value);
    $value = $json;
    foreach ($attributes as $attribute) {
      if (!is_array($value)) {
        return '';
      }
      if (empty($value[$attribute])) {
        '';
      }
      if (!empty($value[$attribute])) {
        $value = $value[$attribute];
        continue;
      }
      if (strpos($attribute, '|') !== FALSE) {
        $attrs = explode('|', $attribute);
        if (!empty($attrs[0]) && strpos($attrs[0], '=') !== FALSE) {
          $key = explode('=', $attrs[0])[0];
          $val = explode('=', $attrs[0])[1];
          $val_key = $attrs[1];
          foreach ($value as $item) {
            if (!empty($item[$key]) && $item[$key] === $val && !empty($item[$val_key])) {
              return $item[$val_key];
            }
          }
          return '';
        }
      }
    }
    return $value;
  }
}
