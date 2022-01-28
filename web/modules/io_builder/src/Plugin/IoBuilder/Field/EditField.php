<?php

namespace Drupal\io_builder\Plugin\IoBuilder\Field;

/**
 * Adds an action to fields to edit that single instance.
 *
 * @package Drupal\io_builder\Plugin\IoBuilder\Field
 *
 * @IoBuilderField(
 *   id = "edit_field",
 *   label = @Translation("Edit Field"),
 *   field_types = {}
 * )
 */
class EditField extends IoBuilderFieldBase {

  public function alterBuild(&$build): void {
    $build = [
      '#theme' => 'io_builder__field_wrapper',
      '#placeholder' => t('Add a field here'),
      '#render' => $build,
      '#entity' => $this->context->getEntity(),
      '#view_mode' => $this->context->getViewMode(),
      '#field' => $this->context->getField(),
    ];
  }

}
