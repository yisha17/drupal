<?php

namespace Drupal\io_builder\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class IoBuilderEnableBlock
 *
 * @package Drupal\io_builder\Plugin\Block
 *
 * @Block(
 *   id = "io_builder_toggle_block",
 *   admin_label=@Translation("Io Builder - Toggle link"),
 *   category="io_builder"
 * )
 */
class IoBuilderToggleBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $accountProxy;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $static = new static(
      $configuration, $plugin_id, $plugin_definition
    );

    $static->setAccountProxy(
      $container->get('current_user')
    );

    return $static;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!$this->accountProxy->hasPermission('access io builder')) {
      return [];
    }

    $destination = Url::fromRoute('<current>')->toString();

    $ioBuilderToggleLink = Url::fromRoute(
      'io_builder.toggle',
      ['destination' => $destination]
    );

    return [
      '#theme' => 'io_builder__toggle_link',
      '#url' => $ioBuilderToggleLink,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(
      parent::getCacheContexts(),
      ['user.permissions']
    );
  }

  /**
   * Sets the current user.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $accountProxy
   *   An account proxy.
   */
  public function setAccountProxy(AccountProxyInterface $accountProxy): void {
    $this->accountProxy = $accountProxy;
  }

}
