<?php

namespace Drupal\io_builder\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\io_builder\Plugin\IoBuilder\Context\IoBuilderEntityFieldContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * The drag and drop controller.
 *
 * @package Drupal\io_builder\Controller
 */
class DragAndDropController extends IoBuilderController {

  /**
   * This function will handle the drag and drop functionality for our IO build.
   */
  public function dragAndDrop(Request $request) {
    $indexSwitch = $request->get('index_switch');
    $from = $indexSwitch['from'] ?? NULL;
    $to = $indexSwitch['to'] ?? NULL;

    if ($indexSwitch['from'] === $indexSwitch['to']) {
      return new AjaxResponse();
    }

    // @todo make this a proper object we can reuse
    $ioBuilderContextTree = $this->getIoBuilderContextTreeFromRequest($request);
    $parent = $ioBuilderContextTree['parent'];

    if (empty($parent)) {
      return new AjaxResponse();
    }

    $context = $this->contextPluginManager->createInstance(
      'io_builder_entity_field_context', $parent + [
        'field' => $request->get('field')
      ]
    );

    if (!$context instanceof IoBuilderEntityFieldContext) {
      return new AjaxResponse();
    }

    $field = $context->getFieldList();
    $iterator = $field->getIterator();
    $values = $iterator->getArrayCopy();
    $fromValue = $values[$from];
    unset($values[$from]);
    $this->arrayInsert($values, (int) $to, [$fromValue]);

    foreach ($values as $key => $value) {
      if ($value instanceof FieldItemInterface) {
        $values[$key] = $value->toArray();
      }
    }

    $field->setValue($values);
    $context->getEntity()->save();
    return $this->rebuildFromContext($context);
  }

}
