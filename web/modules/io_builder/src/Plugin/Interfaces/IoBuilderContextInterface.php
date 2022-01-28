<?php

namespace Drupal\io_builder\Plugin\Interfaces;

/**
 * Describes the methods used for the IO Builder contexts.
 *
 * @package Drupal\io_builder\Plugin\IoBuilder\Context
 */
interface IoBuilderContextInterface {

  /**
   * Returns the route parameters.
   *
   * @return array
   *   An array containing route parameters.
   */
  public function getRouteParameters(): array;

  /**
   * Returns the io builder element data.
   *
   * @return array
   *   An array containing element data.
   */
  public function getIoBuilderElementData(): array;

  /**
   * Returns an array of IO Builder HTML attributes.
   *
   * @return array
   *   An array of IO Builder HTML attributes.
   */
  public function getIoBuilderAttributes(): array;

  /**
   * Returns the Io Builder element selector.
   *
   * @return string
   *   The element selector.
   */
  public function getIoBuilderElementSelector(): string;

  /**
   * Returns the Io Builder element selector.
   *
   * @return string
   *   The element selector.
   */
  public function getIoBuilderElementSelectorDomQuery(): string;

  /**
   * Rebuilds the context.
   *
   * @return mixed
   */
  public function rebuild();

}
