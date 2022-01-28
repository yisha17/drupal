<?php

namespace Drupal\io_builder\Plugin\Interfaces;

use Drupal\io_builder\Traits\IoBuilderEntityContextSetterInterface;

interface IoBuilderEntityActionsInterface extends IoBuilderEntityContextSetterInterface {

  /**
   * Returns an array of actions for a certain entity.
   *
   * @param array $build
   *   An optional build array.
   *
   * @return array
   *   An array of actions.
   */
  public function getActions(array &$build = []): array;

}
