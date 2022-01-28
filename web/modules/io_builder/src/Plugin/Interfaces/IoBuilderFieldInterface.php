<?php

namespace Drupal\io_builder\Plugin\Interfaces;

/**
 * Interface for IO Builder fields.
 *
 * @package Drupal\io_builder\Plugin\Interfaces
 */
interface IoBuilderFieldInterface {

  /**
   * Alters the build.
   *
   * @param $build
   *   The build.
   */
  public function alterBuild(&$build): void;


}
