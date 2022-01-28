<?php

namespace Drupal\io_builder\Plugin\IoBuilder\EntityActions;

use Drupal\io_builder\Plugin\Interfaces\IoBuilderEntityActionsInterface;
use Drupal\io_builder\Traits\IoBuilderEntityContextSetterTrait;

/**
 * An abstract base class for all io builder entity actions.
 *
 * @package Drupal\io_builder\Plugin\IoBuilder\EntityActions
 */
abstract class BaseEntityActions implements IoBuilderEntityActionsInterface {

  use IoBuilderEntityContextSetterTrait;

  /**
   * Returns a base action link.
   */
  public static function baseActionLink(): array {
    return [
      '#theme' => 'io_builder__action',
      '#attributes' => [
        'data-io-builder-action' => 'ajax_action',
      ],
    ];
  }

}
