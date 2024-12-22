<?php

/**
 * @file
 * Builds placeholder replacement tokens for json field data.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Implements hook_token_info_alter().
 */
function kamihaya_cms_json_field_token_info_alter(&$data) {
  $data['types']['json'] = [
    'name' => t('JSON'),
    'description' => t('JSON tokens type.'),
    'needs-data' => 'json',
    'nested' => TRUE,
  ];
  $type_info = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
  foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
    if (!$entity_type->entityClassImplements(ContentEntityInterface::class)) {
      continue;
    }

    $token_type = \Drupal::service('token.entity_mapper')->getTokenTypeForEntityType($entity_type_id);
    if (empty($token_type) || !isset($data['types'][$token_type])) {
      continue;
    }
    $fields = [];
    foreach( ['json', 'json_native', 'json_native_binary'] as $type) {
      $fields = array_merge($fields, \Drupal::entityTypeManager()->getStorage('field_storage_config')->loadByProperties([
        'entity_type' => $entity_type_id,
        'type' => $type
      ]));
    }
    foreach ($fields as $field) {
      $field_token_name = $token_type . '-' . $field->getName();
      $params['@type'] = $type_info[$field->getType()]['label'];
      $description = t('@type field.', $params);

      if (empty($data['tokens'][$token_type][$field->getName()])) {
        $data['tokens'][$token_type][$field->getName()] = [
          'name' => Html::escape($field->getName()),
          'description' => $description,
          'module' => 'kamihaya_cms_json_field',
          'type' => $field_token_name,
        ];
      }
      if (!empty($data['tokens'][$field_token_name]['value']) && empty($data['tokens'][$field_token_name]['json'])) {
        $data['tokens'][$field_token_name]['json'] = [
          'name' => t('JSON'),
          'description' => t('JSON value.'),
          'type' => 'json',
          'module' => 'kamihaya_cms_json_field',
        ];
        unset($data['tokens'][$field_token_name]['value']);
      }
      if (empty($data['types'][$field_token_name])) {
        $data['types'][$field_token_name] = [
          'name' => Html::escape($field->getName()),
          'description' => new TranslatableMarkup('@label tokens.', ['@label' => Html::escape($field->getName())]),
          'needs-data' => $field_token_name,
          'nested' => TRUE,
          'type' => $field_token_name,
          'module' => 'kamihaya_cms_json_field',
        ];
      }
    }
  }
}

/**
 * Implements hook_tokens().
 */
function kamihaya_cms_json_field_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
    if ($entity_type_id !== $type || empty($data[$type])) {
      continue;
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $data[$type];
    if (!($entity instanceof ContentEntityInterface)) {
      return $replacements;
    }

    /** @var \Drupal\Core\Utility\Token $token_service */
    $token_service = \Drupal::token();
    foreach ($tokens as $name => $original) {
      $field_name = explode(':', $name)[0];
      if (!$entity->hasField($field_name) || !in_array($entity->get($field_name)->getFieldDefinition()->getType(), ['json', 'json_native', 'json_native_binary'])) {
        continue;
      }
      $json_tokens = $token_service->findWithPrefix($tokens, $field_name);
      $replacements += $token_service->generate('json', $json_tokens, ['json' => $entity->get($field_name)->value], $options, $bubbleable_metadata);
    }
  }

  if ($type === 'json' && !empty($data['json'])) {
    $json = json_decode($data['json'], TRUE);
    foreach ($tokens as $name => $original) {
      $toke_name = explode(':', $name);
      if (count($toke_name) === 1) {
        $replacements[$original] = json_encode($json);
        continue;
      }
      $value = $json;
      for ($i = 1; $i < count($toke_name); $i++) {
        if (!is_array($value)) {
          continue 2;
        }
        if (!empty($value[$toke_name[$i]])) {
          $value = $value[$toke_name[$i]];
          continue;
        }
        if (strpos($toke_name[$i], '|') !== FALSE) {
          $attrs = explode('|', $toke_name[$i]);
          if (!empty($attrs[0]) && strpos($attrs[0], '=') !== FALSE) {
            $key = explode('=', $attrs[0])[0];
            $val = explode('=', $attrs[0])[1];
            $val_key = $attrs[1];
            foreach ($value as $item) {
              if (!empty($item[$key]) && $item[$key] === $val && !empty($item[$val_key])) {
                $replacements[$original] = $item[$val_key];
                continue 3;
              }
            }
          }
        }
      }
      $replacements[$original] = $value;
    }
  }
  return $replacements;
}
