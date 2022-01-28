<?php

namespace Drupal\io_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\io_builder\PrivateTempStore\IoBuilderSettingsStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * This controller enables the IO builder functionality.
 *
 * @package Drupal\io_builder\Controller
 */
class IoBuilderToggleController extends ControllerBase {

  /**
   * Contains the user session settings for the IO builder.
   *
   * @var \Drupal\io_builder\PrivateTempStore\IoBuilderSettingsStorage
   */
  protected IoBuilderSettingsStorage $settingsStorage;

  /**
   * ToggleIoBuilderController constructor.
   *
   * @param \Drupal\io_builder\PrivateTempStore\IoBuilderSettingsStorage $settingsStorage
   *   The settings storage.
   */
  public function __construct(IoBuilderSettingsStorage $settingsStorage) {
    $this->settingsStorage = $settingsStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('io_builder.temp_store.settings')
    );
  }

  /**
   * This route enables the IO builder for the current user.
   *
   * This will allow the current user to view the IO builder elements.
   *
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects to the original page or homepage.
   */
  public function toggle(Request $request = NULL) {
    $this->settingsStorage->toggleIoBuilder();
    $destination = $request->get('destination');

    if ($destination) {
      $url = Url::fromUserInput($destination)->toString();
    }
    else {
      $url = Url::fromRoute('<front>')->toString();
    }

    $enabled = $this->settingsStorage->ioBuilderEnabled();

    $this->messenger()->addStatus(
      $this->t('IO Builder has been @value', [
        '@value' => $enabled ? 'enabled' : 'disabled'
      ])
    );

    return new RedirectResponse($url);
  }

}
