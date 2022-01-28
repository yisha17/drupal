<?php

namespace Drupal\io_builder\Plugin;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\io_builder\Annotation\IoBuilderField;
use Drupal\io_builder\Plugin\Interfaces\IoBuilderFieldInterface;

/**
 * Manages our IO Builder Field plugins.
 *
 * @package Drupal\io_builder\Plugin
 */
class IoBuilderFieldPluginManager extends DefaultPluginManager {

  /**
   * The field type manager to define field.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * @var null
   */
  protected $widgetOptions = NULL;


  protected $mapper;

  /**
   * Constructs a ParagraphsBehaviorManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    FieldTypePluginManagerInterface $field_type_manager
  ) {
    parent::__construct(
      'Plugin/IoBuilder/Field',
      $namespaces,
      $module_handler,
      IoBuilderFieldInterface::class,
      IoBuilderField::class
    );

    $this->setCacheBackend($cache_backend, 'io_builder_field_plugins');
    $this->alterInfo('io_builder_field_info');
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * Merges default values for formatter configuration.
   *
   * @param string $field_type
   *   The field type.
   * @param array $configuration
   *   An array of formatter configuration.
   *
   * @return array
   *   The display properties with defaults added.
   */
  public function prepareConfiguration($field_type, array $configuration) {
    // Fill in defaults for missing properties.
    $configuration += [
      'label' => 'above',
      'settings' => [],
      'third_party_settings' => [],
    ];

    // If no formatter is specified, use the default formatter.
    if (!isset($configuration['type'])) {
      $configuration['type'] = NULL;
    }

    // Filter out unknown settings, and fill in defaults for missing settings.
    $default_settings = $this->getDefaultSettings($configuration['type']);
    $configuration['settings'] = array_intersect_key($configuration['settings'], $default_settings) + $default_settings;

    return $configuration;
  }

  /**
   * Returns an array of widget type options for a field type.
   *
   * @param string|null $field_type
   *   (optional) The name of a field type, or NULL to retrieve all widget
   *   options. Defaults to NULL.
   *
   * @return array
   *   If no field type is provided, returns a nested array of all widget types,
   *   keyed by field type human name.
   */
  public function getOptions($field_type = NULL) {
    if (!isset($this->widgetOptions)) {
      $options = [];
      $field_types = $this->fieldTypeManager->getDefinitions();
      $widget_types = $this->getDefinitions();
      uasort($widget_types, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
      foreach ($widget_types as $name => $widget_type) {
        if (empty($widget_type['field_types'])) {
          $options['all'][$name] = $widget_type['label'];
        }

        foreach ($widget_type['field_types'] as $widget_field_type) {
          // Check that the field type exists.
          if (isset($field_types[$widget_field_type])) {
            $options[$widget_field_type][$name] = $widget_type['label'];
          }
        }
      }
      $this->widgetOptions = $options;
    }

    if (isset($field_type)) {
      $widgetsForAll = $this->widgetOptions['all'] ?? [];
      $widgetsForType = $this->widgetOptions[$field_type] ?? [];
      return array_merge($widgetsForAll, $widgetsForType);
    }

    return $this->widgetOptions;
  }

  /**
   * Returns the default settings of a field widget.
   *
   * @param string $type
   *   A field widget type name.
   *
   * @return array
   *   The widget type's default settings, as provided by the plugin
   *   definition, or an empty array if type or settings are undefined.
   */
  public function getDefaultSettings($type) {
    $plugin_definition = $this->getDefinition($type, FALSE);
    if (!empty($plugin_definition['class'])) {
      $plugin_class = DefaultFactory::getPluginClass($type, $plugin_definition);
      return $plugin_class::defaultSettings();
    }

    return [];
  }

  /**
   * Overrides PluginManagerBase::getInstance().
   *
   * @param array $options
   *   An array with the following key/value pairs:
   *   - field_definition: (FieldDefinitionInterface) The field definition.
   *   - form_mode: (string) The form mode.
   *   - prepare: (bool, optional) Whether default values should get merged in
   *     the 'configuration' array. Defaults to TRUE.
   *   - configuration: (array) the configuration for the widget. The
   *     following key value pairs are allowed, and are all optional if
   *     'prepare' is TRUE:
   *     - type: (string) The widget to use. Defaults to the
   *       'default_widget' for the field type. The default widget will also be
   *       used if the requested widget is not available.
   *     - settings: (array) Settings specific to the widget. Each setting
   *       defaults to the default value specified in the widget definition.
   *     - third_party_settings: (array) Settings provided by other extensions
   *       through hook_field_formatter_third_party_settings_form().
   *
   * @return \Drupal\Core\Field\WidgetInterface|null
   *   A Widget object or NULL when plugin is not found.
   */
  public function getInstance(array $options) {
    // Fill in defaults for missing properties.
    $options += [
      'configuration' => [],
      'prepare' => TRUE,
    ];

    $configuration = $options['configuration'];
    $field_definition = $options['field_definition'];
    $field_type = $field_definition->getType();

    // Fill in default configuration if needed.
    if ($options['prepare']) {
      $configuration = $this->prepareConfiguration($field_type, $configuration);
    }

    $plugin_id = $configuration['type'];

    if (!$plugin_id) {
      return NULL;
    }

    try {
      return $this->createInstance($plugin_id, $configuration);
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

}
