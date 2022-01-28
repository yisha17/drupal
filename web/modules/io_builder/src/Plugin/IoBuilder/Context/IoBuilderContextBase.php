<?php

namespace Drupal\io_builder\Plugin\IoBuilder\Context;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\io_builder\Plugin\Interfaces\IoBuilderContextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contains the base functions for the IO builder context plugins.
 *
 * @package Drupal\io_builder\Plugin\IoBuilder\Context
 */
abstract class IoBuilderContextBase extends PluginBase implements ContainerFactoryPluginInterface, IoBuilderContextInterface {

  const SELECTOR_HTML_ATTRIBUTE = 'data-io-builder-element';
  const DATA_HTML_ATTRIBUTE = 'data-io-builder-data';
  const ID_KEY = 'io_builder_context_id';

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
  public function getRouteParameters(): array {
    return [
      static::ID_KEY => $this->getPluginId(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIoBuilderAttributes(): array {
    return [
      static::SELECTOR_HTML_ATTRIBUTE => $this->getIoBuilderElementSelector(),
      static::DATA_HTML_ATTRIBUTE => Json::encode(
        $this->getIoBuilderElementData()
      ),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIoBuilderElementSelectorDomQuery(): string {
    return sprintf(
      '[%s="%s"]',
      static::SELECTOR_HTML_ATTRIBUTE,
      $this->getIoBuilderElementSelector()
    );
  }

  /**
   * Returns the configuration of the context.
   *
   * @return array
   *   An array containing the configuration.
   */
  public function getConfiguration(): array {
    if (!is_array($this->configuration) || empty($this->configuration)) {
      return [];
    }

    return $this->configuration;
  }

}
