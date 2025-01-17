<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Plugin\Derivative;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\feeds\Plugin\Derivative\GenericContentEntityProcessor;

/**
 * Provides generic Feeds processor plugin definitions for multi language content entities.
 *
 * @see \Drupal\feeds\Feeds\Processor\GenericContentEntityProcessor
 */
class MultiLanguageGenericContentEntityProcessor extends GenericContentEntityProcessor {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (!($entity_type instanceof ContentEntityTypeInterface) || !$entity_type->hasKey('langcode')) {
        continue;
      }
      if (!in_array($entity_type->id(), ['node', 'media', 'file'])) {
        continue;
      }
      $this->derivatives[$entity_type_id] = $base_plugin_definition;
      $this->derivatives[$entity_type_id]['title'] = $entity_type->getLabel(). '(multi language)';
      $this->derivatives[$entity_type_id]['description'] = $this->t('Creates multi language @plural_label from feed items.', [
        '@plural_label' => $entity_type->getPluralLabel(),
      ]);
      $this->derivatives[$entity_type_id]['entity_type'] = $entity_type_id;
    }
    return $this->derivatives;
  }

}
