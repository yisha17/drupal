<?php

namespace Drupal\io_builder\Plugin\IoBuilder\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\PluginSettingsBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\io_builder\Plugin\Interfaces\IoBuilderFieldInterface;
use Drupal\io_builder\Traits\IoBuilderEntityContextSetterTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class IoBuilderFieldBase extends PluginSettingsBase implements IoBuilderFieldInterface, ContainerFactoryPluginInterface {

  use IoBuilderEntityContextSetterTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // By default, widgets are available for all fields.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [];
  }

  public function settingsForm() {
    return [];
  }

}
