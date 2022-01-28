<?php

namespace Drupal\io_builder\Form;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class FieldForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'io_builder_field_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->buildEntity($form, $form_state);
    $form_state->set('entity', $entity);
    $entity->save();
  }

  /**
   * Returns a cloned entity containing updated field values.
   *
   * Calling code may then validate the returned entity, and if valid, transfer
   * it back to the form state and save it.
   */
  protected function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var $entity \Drupal\Core\Entity\EntityInterface */
    $entity = clone $form_state->get('entity');

    $form_state->get('form_display')->extractFormValues(
      $entity, $form, $form_state
    );

    $form_state->set('entity', $entity);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $entity = NULL, $field_name = NULL) {
    if (!$form_state->has('entity')) {
      $this->init($form_state, $entity, $field_name);
    }

    // Add the field form.
    $form_state->get('form_display')
      ->buildForm($entity, $form, $form_state);

    $this->simplify($form, $form_state);

    $form['#attributes']['data-io-builder-type'] = 'form';
    $form['#io_builder_enable'] = TRUE;

    $form['actions'] = [
      '#type' => 'actions'
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'wrapper' => 'body',
      ],
    ];

    return $form;
  }


  /**
   * Initialize the form state and the entity before the first form build.
   */
  protected function init(FormStateInterface $form_state, EntityInterface $entity, $field_name) {
    $form_state->set('io_builder', TRUE);
    $form_state->set('entity', $entity);
    $form_state->set('field_name', $field_name);

    // Fetch the display used by the form. It is the display for the 'default'
    // form mode, with only the current field visible.
    $display = EntityFormDisplay::collectRenderDisplay($entity, 'io_builder');

    foreach ($display->getComponents() as $name => $options) {
      if ($name != $field_name) {
        $display->removeComponent($name);
        continue;
      }
   }

    $form_state->set('form_display', $display);
  }

  /**
   * Simplifies the field edit form for in-place editing.
   *
   * This function:
   * - Hides the field label inside the form, because JavaScript displays it
   *   outside the form.
   * - Adjusts textarea elements to fit their content.
   *
   * @param array &$form
   *   A reference to an associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function simplify(array &$form, FormStateInterface $form_state) {
    $field_name = $form_state->get('field_name');
    $widget_element = &$form[$field_name]['widget'];
    $widget_element[0]['#io_builder_enable'] = TRUE;

    if (!empty($widget_element['#title'])) {
      $widget_element['#title_display'] = 'invisible';
    }

    $widget_element[0]['#title_display'] = 'invisible';
  }

}
