<?php

namespace Drupal\io_builder\Entity;

use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Entity\EntityDisplayPluginCollection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Configuration entity that contains widget options for all components of an
 * entity form in a given form mode.
 *
 * @ConfigEntityType(
 *   id = "io_builder_display",
 *   label = @Translation("IO Builder Display"),
 *   entity_keys = {
 *     "id" = "id",
 *     "status" = "status"
 *   },
 *   handlers={
 *     "form" = {
 *       "default" = "Drupal\io_builder\FormDisplay\IoBuilderDisplayForm",
 *       "add" = "Drupal\io_builder\FormDisplay\IoBuilderDisplayForm",
 *       "edit" = "Drupal\io_builder\FormDisplay\IoBuilderDisplayForm",
 *     },
 *   },
 *   config_export = {
 *     "id",
 *     "targetEntityType",
 *     "bundle",
 *     "mode",
 *     "content",
 *     "hidden",
 *   }
 * )
 */
class IoBuilderDisplay extends EntityDisplayBase implements EntityFormDisplayInterface {

  /**
   * {@inheritdoc}
   */
  protected $displayContext = 'io_builer_display';

  /**
   * {@inheritdoc}
   */
  public function getRenderer($field_name) {
    if (isset($this->plugins[$field_name])) {
      return $this->plugins[$field_name];
    }

    // Instantiate the widget object from the stored display properties.
    if (($configuration = $this->getComponent($field_name)) && !empty($configuration['type']) && ($definition = $this->getFieldDefinition($field_name))) {
      $widget = $this->pluginManager->getInstance([
        'field_definition' => $definition,
        'form_mode' => $this->originalMode,
        // No need to prepare, defaults have been merged in setComponent().
        'prepare' => FALSE,
        'configuration' => $configuration,
      ]);
    }
    else {
      $widget = NULL;
    }

    // Persist the widget object.
    $this->plugins[$field_name] = $widget;
    return $widget;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    $this->pluginManager = \Drupal::service('plugin.manager.io_builder_field');

    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(FieldableEntityInterface $entity, array &$form, FormStateInterface $form_state) {
    // Set #parents to 'top-level' by default.
    $form += ['#parents' => []];

    // Let each widget generate the form elements.
    foreach ($this->getComponents() as $name => $options) {
      if ($widget = $this->getRenderer($name)) {
        $items = $entity->get($name);
        $items->filterEmptyItems();
        $form[$name] = $widget->form($items, $form, $form_state);
        $form[$name]['#access'] = $items->access('edit');

        // Assign the correct weight. This duplicates the reordering done in
        // processForm(), but is needed for other forms calling this method
        // directly.
        $form[$name]['#weight'] = $options['weight'];

        // Associate the cache tags for the field definition & field storage
        // definition.
        $field_definition = $this->getFieldDefinition($name);
        $this->renderer->addCacheableDependency($form[$name], $field_definition);
        $this->renderer->addCacheableDependency($form[$name], $field_definition->getFieldStorageDefinition());
      }
    }

    // Associate the cache tags for the form display.
    $this->renderer->addCacheableDependency($form, $this);

    // Add a process callback so we can assign weights and hide extra fields.
    $form['#process'][] = [$this, 'processForm'];
  }

  /**
   * Process callback: assigns weights and hides extra fields.
   *
   * @see \Drupal\Core\Entity\Entity\EntityFormDisplay::buildForm()
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    // Assign the weights configured in the form display.
    foreach ($this->getComponents() as $name => $options) {
      if (isset($element[$name])) {
        $element[$name]['#weight'] = $options['weight'];
      }
    }

    // Hide extra fields.
    $extra_fields = \Drupal::service('entity_field.manager')->getExtraFields($this->targetEntityType, $this->bundle);
    $extra_fields = isset($extra_fields['form']) ? $extra_fields['form'] : [];
    foreach ($extra_fields as $extra_field => $info) {
      if (!$this->getComponent($extra_field)) {
        $element[$extra_field]['#access'] = FALSE;
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldableEntityInterface $entity, array &$form, FormStateInterface $form_state) {
    $extracted = [];
    foreach ($entity as $name => $items) {
      if ($widget = $this->getRenderer($name)) {
        $widget->extractFormValues($items, $form, $form_state);
        $extracted[$name] = $name;
      }
    }
    return $extracted;
  }

  /**
   * {@inheritdoc}
   */
  public function validateFormValues(FieldableEntityInterface $entity, array &$form, FormStateInterface $form_state) {
    $violations = $entity->validate();
    $violations->filterByFieldAccess();

    // Flag entity level violations.
    foreach ($violations->getEntityViolations() as $violation) {
      /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      $form_state->setError($form, $violation->getMessage());
    }

    $this->flagWidgetsErrorsFromViolations($violations, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function flagWidgetsErrorsFromViolations(EntityConstraintViolationListInterface $violations, array &$form, FormStateInterface $form_state) {
    $entity = $violations->getEntity();
    foreach ($violations->getFieldNames() as $field_name) {
      // Only show violations for fields that actually appear in the form, and
      // let the widget assign the violations to the correct form elements.
      if ($widget = $this->getRenderer($field_name)) {
        $field_violations = $this->movePropertyPathViolationsRelativeToField($field_name, $violations->getByField($field_name));
        $widget->flagErrors($entity->get($field_name), $field_violations, $form, $form_state);
      }
    }
  }

  /**
   * Moves the property path to be relative to field level.
   *
   * @param string $field_name
   *   The field name.
   * @param \Symfony\Component\Validator\ConstraintViolationListInterface $violations
   *   The violations.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationList
   *   A new constraint violation list with the changed property path.
   */
  protected function movePropertyPathViolationsRelativeToField($field_name, ConstraintViolationListInterface $violations) {
    $new_violations = new ConstraintViolationList();
    foreach ($violations as $violation) {
      // All the logic below is necessary to change the property path of the
      // violations to be relative to the item list, so like title.0.value gets
      // changed to 0.value. Sadly constraints in Symfony don't have setters so
      // we have to create new objects.
      /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      // Create a new violation object with just a different property path.
      $violation_path = $violation->getPropertyPath();
      $path_parts = explode('.', $violation_path);
      if ($path_parts[0] === $field_name) {
        unset($path_parts[0]);
      }
      $new_path = implode('.', $path_parts);

      $constraint = NULL;
      $cause = NULL;
      $parameters = [];
      $plural = NULL;
      if ($violation instanceof ConstraintViolation) {
        $constraint = $violation->getConstraint();
        $cause = $violation->getCause();
        $parameters = $violation->getParameters();
        $plural = $violation->getPlural();
      }

      $new_violation = new ConstraintViolation(
        $violation->getMessage(),
        $violation->getMessageTemplate(),
        $parameters,
        $violation->getRoot(),
        $new_path,
        $violation->getInvalidValue(),
        $plural,
        $violation->getCode(),
        $constraint,
        $cause
      );
      $new_violations->add($new_violation);
    }
    return $new_violations;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'widgets' => new EntityDisplayPluginCollection($this->pluginManager, []),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // Ensure that a region is set on each component.
    foreach ($this->getComponents() as $name => $component) {
      // Ensure that a region is set.
      if (isset($this->content[$name]) && !isset($component['region'])) {
        // Directly set the component to bypass other changes in setComponent().
        $this->content[$name]['region'] = $this->getDefaultRegion();
      }
    }

    ksort($this->content);
    ksort($this->hidden);
    parent::preSave($storage);
  }

}
