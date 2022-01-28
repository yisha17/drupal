<?php

namespace Drupal\io_builder\Plugin\IoBuilder\Context;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\io_builder\Traits\IoBuilderFieldSetterTrait;

/**
 * Creates a content for manipulating a field on a specific entity view mode.
 *
 * @package Drupal\io_builder\Plugin\IoBuilder\Context
 *
 * @IoBuilderContext(
 *   id = "io_builder_entity_field_context"
 * )
 */
class IoBuilderEntityFieldContext extends IoBuilderEntityContext {

  use IoBuilderFieldSetterTrait;

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return parent::getRouteParameters() + [
      'field' => $this->getField()
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setProperties() {
    parent::setProperties();
    $field = $this->configuration['field'] ?? NULL;

    if (!empty($field) && is_string($field)) {
      $this->setField($field);
    }
  }

  /**
   * Returns the field item list.
   *
   * @return \Drupal\Core\TypedData\ListInterface
   *   The list interface.
   */
  public function getFieldList(): ?ListInterface {
    if (!$this->entity instanceof ContentEntityBase || !$this->field) {
      return NULL;
    }

    if (!$this->entity->hasField($this->field)) {
      return NULL;
    }

    $fieldItemList = $this->entity->get($this->field);

    if (!$fieldItemList instanceof ListInterface) {
      return NULL;
    }

    return $fieldItemList;
  }

}
