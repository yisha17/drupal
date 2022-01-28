<?php

/**
 * @file
 * Preprocessors for fieldgroup elements.
 */

use Drupal\Core\Template\Attribute;

/**
 * Preprocesses the field wrapper.
 *
 * @param array $variables
 *   Array containing variables.
 */
function template_preprocess_io_builder__field_wrapper(array &$variables) {
  /** @var \Drupal\io_builder\Utility\FieldUtility\EntityFieldUtility $fieldUtility */
  $fieldUtility = \Drupal::service('io_builder.utility.field.entity_field');
  $fieldUtility->setEntity($variables['entity'] ?? NULL);
  $fieldUtility->setRender($variables['render'] ?? NULL);
  $fieldUtility->setField($variables['field'] ?? NULL);
  $fieldUtility->setViewMode($variables['view_mode'] ?? 'default');
  $fieldUtility->extendVariables($variables);
}
