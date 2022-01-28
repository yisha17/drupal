<?php

namespace Drupal\io_builder\Traits;

use Drupal\io_builder\Plugin\IoBuilder\Context\IoBuilderEntityContextInterface;

/**
 * Describes methods for setting and getting the IoBuilderEntityContext.
 *
 * @package Drupal\io_builder\Traits
 */
interface IoBuilderEntityContextSetterInterface {

  /**
   * Gets the context.
   *
   * @return \Drupal\io_builder\IoBuilderContext\Plugin\IoBuilder|null
   *   The IoBuilderEntityContext.
   */
  public function getContext(): ?IoBuilderEntityContextInterface;

  /**
   * Sets the IoBuilderEntityContext.
   *
   * @param \Drupal\io_builder\Plugin\IoBuilder\Context\IoBuilderEntityContextInterface $context
   *   The IoBuilderEntityContext.
   */
  public function setContext(IoBuilderEntityContextInterface $context): void;

}
