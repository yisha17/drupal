<?php

namespace Drupal\io_builder\FormDisplay;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\PluginSettingsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class IoBuilderDisplayForm extends EntityDisplayFormBase {

  /**
   * The display context. Either 'view' or 'form'.
   *
   * @var string
   */
  protected $displayContext = 'form';

  /**
   * The widget or formatter plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerBase
   */
  protected $pluginManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;


  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * A list of field types.
   *
   * @var array
   */
  protected $fieldTypes;

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\Core\Entity\Display\EntityDisplayInterface
   */
  protected $entity;

  /**
   * Constructs a new EntityDisplayFormBase.
   *
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type manager.
   * @param \Drupal\Component\Plugin\PluginManagerBase $plugin_manager
   *   The widget or formatter plugin manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface|null $entity_display_repository
   *   (optional) The entity display_repository.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface|null $entity_field_manager
   *   (optional) The entity field manager.
   */
  public function __construct(
    FieldTypePluginManagerInterface $field_type_manager,
    PluginManagerBase $plugin_manager,
    EntityDisplayRepositoryInterface $entity_display_repository,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    $this->fieldTypes = $field_type_manager->getDefinitions();
    $this->pluginManager = $plugin_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.field.field_type'),
      $container->get('plugin.manager.io_builder_field'),
      $container->get('entity_display.repository'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Collects the definitions of fields whose display is configurable.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The array of field definitions
   */
  protected function getFieldDefinitions() {
    $context = $this->displayContext;

    return array_filter($this->entityFieldManager->getFieldDefinitions($this->entity->getTargetEntityTypeId(), $this->entity->getTargetBundle()), function (FieldDefinitionInterface $field_definition) use ($context) {
      return $field_definition->isDisplayConfigurable($context);
    });
  }


  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $field_definitions = $this->getFieldDefinitions();

    // @todo add better message when no fields are added.
    if (empty($field_definitions)) {
      return [];
    }

    $form += [
      '#entity_type' => $this->entity->getTargetEntityTypeId(),
      '#bundle' => $this->entity->getTargetBundle(),
      '#fields' => array_keys($field_definitions),
    ];

    $table = [
      '#type' => 'table',
      '#header' => $this->getTableHeader(),
      '#attributes' => [
        'class' => ['field-ui-overview'],
        'id' => 'field-display-overview',
      ],
    ];

    // Field rows.
    foreach ($field_definitions as $field_name => $field_definition) {
      $table[$field_name] = $this->buildFieldRow($field_definition, $form, $form_state);
    }

    $form['fields'] = $table;

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildFieldRow(FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    $field_name = $field_definition->getName();
    $display_options = $this->entity->getComponent($field_name);
    $label = $field_definition->getLabel();

    // Disable fields without any applicable plugins.
    if (empty($this->getApplicablePluginOptions($field_definition))) {
      $this->entity->removeComponent($field_name)->save();
      $display_options = $this->entity->getComponent($field_name);
    }

    $field_row = [
      '#row_type' => 'field',
      'human_name' => [
        '#plain_text' => $label,
      ],
    ];

    $field_row['plugin'] = [
      'type' => [
        '#type' => 'select',
        '#title' => $this->t('Plugin for @title', ['@title' => $label]),
        '#title_display' => 'invisible',
        '#options' => $this->getApplicablePluginOptions($field_definition),
        '#default_value' => $display_options ? $display_options['type'] : NULL,
        '#parents' => ['fields', $field_name, 'type'],
        '#attributes' => ['class' => ['field-plugin-type']],
        '#empty_option' => $this->t('- None -'),
        '#required' => FALSE,
      ],
      'settings_edit_form' => [],
    ];

    // Get the corresponding plugin object.
    $plugin = $this->entity->getRenderer($field_name);

    // Base button element for the various plugin settings actions.
    $base_button = [
      '#submit' => ['::multistepSubmit'],
      '#ajax' => [
        'callback' => '::multistepAjax',
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
      ],
      '#field_name' => $field_name,
    ];

    if ($form_state->get('plugin_settings_edit') == $field_name) {
      // We are currently editing this field's plugin settings. Display the
      // settings form and submit buttons.
      $field_row['plugin']['settings_edit_form'] = [];

      if ($plugin) {
        // Generate the settings form and allow other modules to alter it.
        $settings_form = $plugin->settingsForm($form, $form_state);
        $third_party_settings_form = $this->thirdPartySettingsForm($plugin, $field_definition, $form, $form_state);

        if ($settings_form || $third_party_settings_form) {
          $field_row['plugin']['#cell_attributes'] = ['colspan' => 3];
          $field_row['plugin']['settings_edit_form'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['field-plugin-settings-edit-form']],
            '#parents' => ['fields', $field_name, 'settings_edit_form'],
            'label' => [
              '#markup' => $this->t('Plugin settings'),
            ],
            'settings' => $settings_form,
            'third_party_settings' => $third_party_settings_form,
            'actions' => [
              '#type' => 'actions',
              'save_settings' => $base_button + [
                  '#type' => 'submit',
                  '#button_type' => 'primary',
                  '#name' => $field_name . '_plugin_settings_update',
                  '#value' => $this->t('Update'),
                  '#op' => 'update',
                ],
              'cancel_settings' => $base_button + [
                  '#type' => 'submit',
                  '#name' => $field_name . '_plugin_settings_cancel',
                  '#value' => $this->t('Cancel'),
                  '#op' => 'cancel',
                  // Do not check errors for the 'Cancel' button, but make sure we
                  // get the value of the 'plugin type' select.
                  '#limit_validation_errors' => [['fields', $field_name, 'type']],
                ],
            ],
          ];
          $field_row['#attributes']['class'][] = 'field-plugin-settings-editing';
        }
      }
    }
    else {
      $field_row['settings_summary'] = [];
      $field_row['settings_edit'] = [];

      if ($plugin) {
        // Display a summary of the current plugin settings, and (if the
        // summary is not empty) a button to edit them.
        $summary = $plugin->settingsSummary();

        // Allow other modules to alter the summary.
        $this->alterSettingsSummary($summary, $plugin, $field_definition);

        if (!empty($summary)) {
          $field_row['settings_summary'] = [
            '#type' => 'inline_template',
            '#template' => '<div class="field-plugin-summary">{{ summary|safe_join("<br />") }}</div>',
            '#context' => ['summary' => $summary],
            '#cell_attributes' => ['class' => ['field-plugin-summary-cell']],
          ];
        }

        // Check selected plugin settings to display edit link or not.
        $settings_form = $plugin->settingsForm($form, $form_state);
        $third_party_settings_form = $this->thirdPartySettingsForm($plugin, $field_definition, $form, $form_state);
        if (!empty($settings_form) || !empty($third_party_settings_form)) {
          $field_row['settings_edit'] = $base_button + [
              '#type' => 'image_button',
              '#name' => $field_name . '_settings_edit',
              '#src' => 'core/misc/icons/787878/cog.svg',
              '#attributes' => ['class' => ['field-plugin-settings-edit'], 'alt' => $this->t('Edit')],
              '#op' => 'edit',
              // Do not check errors for the 'Edit' button, but make sure we get
              // the value of the 'plugin type' select.
              '#limit_validation_errors' => [['fields', $field_name, 'type']],
              '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
              '#suffix' => '</div>',
            ];
        }
      }
    }

    return $field_row;
  }

  /**
   * @return array
   */
  protected function getTableHeader() {
    return [
      $this->t('Field'),
      $this->t('IO Builder Field'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityDisplay($entity_type_id, $bundle, $mode) {
    $storage = $this->entityTypeManager->getStorage('io_builder_display');

    // Try loading the entity from configuration; if not found, create a fresh
    // entity object. We do not preemptively create new entity form display
    // configuration entries for each existing entity type and bundle whenever a
    // new form mode becomes available. Instead, configuration entries are only
    // created when an entity form display is explicitly configured and saved.
    $ioBuilderDisplay = $storage->load($entity_type_id . '.' . $bundle . '.default');

    if (!$ioBuilderDisplay) {
      $ioBuilderDisplay = $storage->create([
        'targetEntityType' => $entity_type_id,
        'bundle' => $bundle,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    return $ioBuilderDisplay;
  }

  protected function getDefaultPlugin($field_type) {
    // TODO: Implement getDefaultPlugin() method.
  }

  protected function getDisplayModes() {
    // TODO: Implement getDisplayModes() method.
  }

  protected function getDisplayModeOptions() {
    // TODO: Implement getDisplayModeOptions() method.
  }

  protected function getDisplayModesLink() {
    // TODO: Implement getDisplayModesLink() method.
  }

  protected function getOverviewUrl($mode) {
    // TODO: Implement getOverviewUrl() method.
  }

  protected function thirdPartySettingsForm(PluginSettingsInterface $plugin, FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    // TODO: Implement thirdPartySettingsForm() method.
  }

  protected function alterSettingsSummary(array &$summary, PluginSettingsInterface $plugin, FieldDefinitionInterface $field_definition) {
    // TODO: Implement alterSettingsSummary() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    $route_parameters = $route_match->getParameters()->all();

    return $this->getEntityDisplay($route_parameters['entity_type_id'], $route_parameters['bundle'], $route_parameters[$this->displayContext . '_mode_name'] ?? NULL);
  }

}
