<?php

namespace Drupal\io_builder\Traits;

use Drupal\io_builder\Plugin\IoBuilder\Context\IoBuilderEntityContextInterface;

/**
 * Provides setter/getter functionality for an IO Builder Entity context.
 *
 * @package Drupal\io_builder\Traits
 */
trait IoBuilderEntityContextSetterTrait {

  protected IoBuilderEntityContextInterface $context;

  /**
   * Gets the context.
   *
   * @return \Drupal\io_builder\Plugin\Interfaces\IoBuilderContextInterface|null
   *   The IoBuilderEntityContext.
   */
  public function getContext(): ?IoBuilderEntityContextInterface {
    return $this->context;
  }

  /**
   * Sets the IoBuilderEntityContext.
   *
   * @param \Drupal\io_builder\Plugin\IoBuilder\Context\IoBuilderEntityContextInterface $context
   *   The IoBuilderEntityContext.
   */
  public function setContext(IoBuilderEntityContextInterface $context): void {
    $this->context = $context;
  }

}
