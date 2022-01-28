<?php

namespace Drupal\io_builder\Form;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * This class will build the io builder form display for an entity.
 *
 * If no io_builder form display is added, the "default" display will be shown.
 *
 * @package Drupal\io_builder\Form
 */
class IoBuilderEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['#io_builder_enable'] = TRUE;
    $form = parent::form($form, $form_state);
    $form['#process'][] = [$this, 'processIoBuilderForm'];

    // Sometimes additional contexts are passed.
    // We will serialize these and store them so we can re-use them later on.
    $ioBuilderContext = $form_state->get('io_builder_context_tree') ?? NULL;

    if ($ioBuilderContext) {
      $ioBuilderContext = json_encode($ioBuilderContext);
    }
    else {
      $input = $form_state->getUserInput();
      $ioBuilderContext = $input['io_builder_context'] ?? NULL;
    }

    if ($ioBuilderContext) {
      $form['io_builder_context'] = [
        '#type' => 'hidden',
        '#value' => $ioBuilderContext,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormDisplay(FormStateInterface $form_state) {
    return parent::getFormDisplay($form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // We do not need the delete functionality here.
    if (isset($actions['delete'])) {
      unset($actions['delete']);
    }

    // Make sure the other actions are triggered using ajax.
    foreach ($actions as &$action) {
      $action['#ajax'] = [
        'effect' => 'fade',
      ];
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if ($ioBuilderContext = $form_state->getValue('io_builder_context')) {
      $form_state->set('io_builder_context', json_decode($ioBuilderContext, TRUE));
      $form_state->unsetValue('io_builder_context');
    }

    return parent::save($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);
    $form_state->set('entity', $entity);
    return $entity;
  }

  /**
   * Adds additional processing to our form.
   *
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $form
   */
  public function processIoBuilderForm($element, FormStateInterface $form_state, $form) {
    $fields = $this->getFields();

    foreach ($fields as $field) {
      if (empty($element[$field])) {
        continue;
      }

      $fieldElement = &$element[$field];
      $fieldElement['#type'] = 'details';
      $fieldElement['#open'] = FALSE;
      $fieldElement['#title'] = $fieldElement['widget']['#title'] ?? $field;
      $fieldElement['#description'] = $fieldElement['widget']['#description'] ?? NULL;
      $fieldElement['#io_builder_enable'] = TRUE;
    }

    // Loop over and remove the advanced grouped items, for it sucks.
    foreach ($element as $key => &$elem) {
      if (empty($elem['#group']) || $elem['#group'] !== 'advanced') {
        continue;
      }

      unset($elem['#group']);
    }

    if (empty($element['#fieldgroups'])) {
      $element['#fieldgroups']['group_tabs'] = $this->buildTabsGroup();
      $element['#fieldgroups']['group_varia'] = $this->buildVariaTab('Content', $fields);
    }
    else {
      $element['#fieldgroups']['group_varia'] = $this->buildVariaTab();
    }

    return $element;
  }

  /**
   * Returns an array of fields.
   */
  protected function getFields(): array {
    if (!$this->entity instanceof ContentEntityBase) {
      return [];
    }

    $fieldDefinitions = $this->entity->getFieldDefinitions();

    if (empty($fieldDefinitions)) {
      return [];
    }

    return array_keys($fieldDefinitions);
  }

  protected function buildVariaTab(string $title = 'Other', array $children = []): \stdClass {
    $variaTab = new \stdClass();

    $variaTab->parent_name = 'group_tabs';
    $variaTab->weight = 9999;
    $variaTab->format_type = 'tab';
    $variaTab->region = 'content';
    $variaTab->children = $children + [
      'menu',
      'revision',
      'revision_information',
      'status',
    ];

    $variaTab->format_settings = [
      'id' => '',
      'classes' => '',
      'description' => '',
      'formatter' => 'closed',
      'required_fields' => FALSE,
    ];

    $variaTab->label = $title;
    $variaTab->group_name = 'group_varia';
    $variaTab->entity_type = 'node';
    $variaTab->bundle = 'page';
    $variaTab->context = 'form';
    $variaTab->mode = 'io_builder';
    return $variaTab;
  }

  protected function buildTabsGroup() {
    $variaTab = new \stdClass();

    $variaTab->parent_name = '';
    $variaTab->weight = 0;
    $variaTab->format_type = 'tabs';
    $variaTab->region = 'content';
    $variaTab->children = [];

    $variaTab->format_settings = [
      'id' => '',
      'classes' => '',
      'direction' => 'horizontal',
    ];

    $variaTab->label = 'Tabs';
    $variaTab->group_name = 'group_tabs';
    $variaTab->entity_type = 'node';
    $variaTab->bundle = 'page';
    $variaTab->context = 'form';
    $variaTab->mode = 'io_builder';
    return $variaTab;
  }

}
