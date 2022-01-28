<?php

namespace Drupal\io_builder_paragraphs\Plugin\IoBuilder\Context;

use Drupal\io_builder\Plugin\IoBuilder\Context\IoBuilderEntityFieldContext;

/**
 * Class IoBuilderParagraphsContext
 *
 * @package Drupal\io_builder_paragraphs\Plugin\IoBuilder\Context
 *
 * @IoBuilderContext(
 *   id = "io_builder_paragraph_field_context"
 * )
 */
class IoBuilderParagraphFieldContext extends IoBuilderEntityFieldContext {

  /**
   * Sets the delta of the paragraph.
   *
   * @var int|null
   */
  protected ?int $delta = NULL;

  /**
   * @return int|null
   */
  public function getDelta(): ?int {
    return $this->delta;
  }

  /**
   * @param int|null $delta
   */
  public function setDelta(int $delta): void {
    $this->delta = $delta;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    $parameters = parent::getRouteParameters();
    $parameters['delta'] = $this->getDelta();
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  protected function setProperties() {
    parent::setProperties();
    $delta = $this->configuration['delta'] ?? NULL;

    // Make sure empty strings are converted to NULL.
    if (!is_int($delta) && empty($delta)) {
      $delta = NULL;
    }

    if (!is_null($delta)) {
      $this->setDelta((int) $delta);
    }
  }

}
