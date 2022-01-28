<?php

namespace Drupal\io_builder\Controller;

use Drupal\io_builder\Plugin\IoBuilder\Context\IoBuilderEntityContextInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contains entity related functionality for the IO Builder.
 *
 * @package Drupal\io_builder\Controller
 */
class IoBuilderEntityController extends IoBuilderController {

  /**
   * This class will render the form for the requested entity.
   *
   * The IO builder context will be determined in the param converter.
   */
  public function form(Request $request) {
    try {
      $context = $this->getIoBuilderContextFromRequest(
        $request
      );
    }
    catch (\Exception $e) {
      return $this->displayErrors([
        'Could not get the correct context'
      ]);
    }

    if (!$context instanceof IoBuilderEntityContextInterface) {
      return $this->displayErrors([
        'Could not get the correct context'
      ]);
    }

    $this->initEntity(
      $context->getEntity(), $context->getViewMode()
    );

    // Prepare the form in the entity form builder utility.
    $this->entityFormBuilderUtility
      ->setContext($context)
      ->prepareEntityForm([
      'io_builder_context_tree' => $this->getIoBuilderContextTreeFromRequest($request)
    ]);

    if ($this->entityFormBuilderUtility->isExecuted()) {
      return $this->rebuildFromContext(
        $this->entityFormBuilderUtility->getRebuildContext()
      );
    }
    else if ($errors = $this->entityFormBuilderUtility->getErrors()) {
      return $this->displayErrors($errors);
    }

    return $this->displayIoBuilderPanelCommand(
      $this->entityFormBuilderUtility->getForm()
    );
  }

}
