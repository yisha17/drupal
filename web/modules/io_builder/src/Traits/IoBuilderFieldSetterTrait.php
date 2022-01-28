<?php

namespace Drupal\io_builder\Traits;

/**
 * Trait IoBuilderFieldSetterTrait
 *
 * @package Drupal\io_builder\Traits
 */
trait IoBuilderFieldSetterTrait {

  /**
   * The field name.
   *
   * @var string|null
   */
  protected ?string $field = NULL;

  /**
   * Sets the field.
   *
   * @param string $field
   *   The field name.
   */
  public function setField(string $field): void {
    $this->field = $field;
  }

  /**
   * Returns the field name.
   *
   * @return string|null
   *   The field name.
   */
  public function getField(): ?string {
    return $this->field ?? NULL;
  }

}
