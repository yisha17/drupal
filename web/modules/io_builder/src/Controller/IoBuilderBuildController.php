<?php

namespace Drupal\io_builder\Controller;

use Drupal\Core\Controller\ControllerBase;

class IoBuilderBuildController extends ControllerBase {

  public function build() {
    return [
      '#markup' => '<p>Test</p>'
    ];
  }

}
