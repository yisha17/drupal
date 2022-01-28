<?php

namespace Drupal\io_builder_paragraphs\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\io_builder\Controller\IoBuilderController;
use Drupal\io_builder_paragraphs\Plugin\IoBuilder\Context\IoBuilderParagraphFieldContext;
use Drupal\io_builder_paragraphs\Utility\IoBuilderParagraphsUtility;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contains the functionality to add/edit/remove paragraphs using IO builder.
 *
 * @package Drupal\io_builder_paragraphs\Controller
 */
class IoBuilderParagraphsController extends IoBuilderController {

  /**
   * The paragraphs utility.
   *
   * @var \Drupal\io_builder_paragraphs\Utility\IoBuilderParagraphsUtility
   */
  protected IoBuilderParagraphsUtility $paragraphsUtility;


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $static = parent::create($container);

    $static->setParagraphsUtility(
      $container->get('io_builder_paragraphs.utility.paragraphs')
    );

    return $static;
  }

  /**
   * Adds a paragraph.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request containing the paragraph info.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax response.
   */
  public function add(Request $request) {
    $context = $this->getIoBuilderContextFromRequest($request);

    if (!$context instanceof IoBuilderParagraphFieldContext) {
      return $this->displayErrors([
        'could not convert context to an io builder paragraph field context'
      ]);
    }

    // No type has been selected yet, so we will return the selector first.
    $type = $request->get('type') ?? NULL;

    if (empty($type)) {
      return $this->createModalResponse(
        $this->t('Select paragraph'),
        $this->paragraphsUtility->buildParagraphSelector($context),
      );
    }

    $ajaxResponse = new AjaxResponse();
    $closeDialogCommand = new CloseDialogCommand('#io-builder-dialog');
    $ajaxResponse->addCommand($closeDialogCommand);

    // Retrieve a context for our paragraph entity.
    $paragraphContext = $this->contextPluginManager->createInstance(
      'io_builder_entity_context', [
      'entity' => Paragraph::create([
        'type' => $type,
      ]),
    ]);

    $this->entityFormBuilderUtility
      ->setContext($paragraphContext)
      ->prepareEntityForm();

    $form_state = $this->entityFormBuilderUtility->getFormState();

    // Let's return the ajax response from the form state.
    if ($form_state->isExecuted()) {
      $paragraph = $form_state->get('entity');

      $this->insertFieldValue(
        $context->getFieldList(),
        $paragraph,
        $context->getDelta()
      );

      $context->getEntity()->save();
      return $this->rebuildFromContext($context);
    }

    return $this->displayIoBuilderPanelCommand(
      $this->entityFormBuilderUtility->getForm()
    );
  }

  /**
   * Sets the paragraphs utility.
   *
   * @param \Drupal\io_builder_paragraphs\Utility\IoBuilderParagraphsUtility $paragraphsUtility
   *   The paragraphs utility.
   */
  public function setParagraphsUtility(IoBuilderParagraphsUtility $paragraphsUtility): void {
    $this->paragraphsUtility = $paragraphsUtility;
  }

  /**
   * Deletes a paragraph.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   */
  public function delete(Request $request) {
    $force = $request->get('force') ?? NULL;
    $context = $this->getIoBuilderContextFromRequest($request);

    if (!$context instanceof IoBuilderParagraphFieldContext) {
      return $this->displayErrors([
        'The context as not of the IO builder pagraph field context type'
      ]);
    }

    if (!$force) {
      return $this->createModalResponse(
        $this->t('Are you sure you wish to remove this paragraph?'),
        $this->paragraphsUtility->createDeleteLink($context)
      );
    }

    if (is_int($context->getDelta())) {
      $field = $context->getFieldList();
      $field->removeItem($context->getDelta());
      $context->getEntity()->save();
    }

    return $this->rebuildFromContext($context);
  }

}
