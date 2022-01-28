<?php

namespace Drupal\io_builder\CacheContext;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\io_builder\Manager\TargetGroupManager;
use Drupal\io_builder\PrivateTempStore\IoBuilderSettingsStorage;

/**
 * Adds a cache context for the io_builder_enabled.
 *
 * Cache context ID: 'io_builder_enabled'.
 *
 * @package Drupal\io_builder\CacheContext
 */
class IoBuilderEnabledCacheContext implements CacheContextInterface {

  use StringTranslationTrait;

  /**
   * @var IoBuilderSettingsStorage
   */
  private IoBuilderSettingsStorage $settingsStorage;

  /**
   * IoBuilderEnabledCacheContext constructor.
   *
   * @param IoBuilderSettingsStorage $settingsStorage
   *   The IO builder settings storage.
   */
  public function __construct(IoBuilderSettingsStorage $settingsStorage) {
    $this->settingsStorage = $settingsStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Io Builder Enabled');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return (int) $this->settingsStorage->ioBuilderEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
