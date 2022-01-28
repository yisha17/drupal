<?php

namespace Drupal\io_builder_paragraphs\Plugin\IoBuilder\Field;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Drupal\io_builder\Plugin\IoBuilder\Context\IoBuilderEntityContextInterface;
use Drupal\io_builder\Plugin\IoBuilder\Context\IoBuilderEntityFieldContext;
use Drupal\io_builder\Plugin\IoBuilder\Field\IoBuilderFieldBase;
use Drupal\io_builder\Plugin\IoBuilderContextPluginManager;
use Drupal\io_builder_paragraphs\Utility\IoBuilderParagraphsUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds IO builder functionality to paragraph fields.
 *
 * @package Drupal\io_builder_paragraphs\Plugin\IoBuilder\Field
 *
 * @IoBuilderField(
 *   id = "io_builder_paragraphs",
 *   label = @Translation ("Io Builder Paragraphs"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class IoBuilderParagraphsField extends IoBuilderFieldBase {

  use StringTranslationTrait;

  /**
   * This utility contains functions that we use accross multiple classes.
   *
   * @var \Drupal\io_builder_paragraphs\Utility\IoBuilderParagraphsUtility
   */
  protected IoBuilderParagraphsUtility $utility;
  /**
   * The context plugin manager is used to create a paragraph context.
   *
   * @var \Drupal\io_builder\Plugin\IoBuilderContextPluginManager
   */
  protected IoBuilderContextPluginManager $contextPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $static = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $static->setUtility(
      $container->get('io_builder_paragraphs.utility.paragraphs')
    );

    $static->setContextPluginManager(
      $container->get('plugin.manager.io_builder_context')
    );

    return $static;
  }

  /**
   * {@inheritdoc}
   *
   * Converting the context to an IO Builder paragraph context here.
   */
  public function setContext(IoBuilderEntityContextInterface $context): void {
    $this->context = $this->contextPluginManager->createInstance(
      'io_builder_paragraph_field_context', $context->getConfiguration()
    );
  }

  /**
   * Adds additional IO builder functionality to a paragraphs field.
   */
  public function alterBuild(&$build): void {
    if (!$this->context instanceof IoBuilderEntityFieldContext) {
      return;
    }

    // Get current field size.
    // todo maybe change the field to an internal context function?
    $entity = $this->context->getEntity();

    if (!$entity->hasField($this->context->getField())) {
      return;
    }

    $field = $entity->get(
      $this->context->getField()
    );

    if (!$field instanceof EntityReferenceRevisionsFieldItemList) {
      return;
    }

    // Get cardinality.
    // Todo make sure that cardinality is taken in to account!!!!!
    $size = $field->count();

    if ($size === 0) {
      $build[0] = [
        '#theme' => 'io_builder__placeholder',
        '#content' => $this->utility->buildAddMore(
          $this->context
        ),
      ];
      return;
    }

    // Add link to build array.
    for ($i = 0; $i < $size; $i++) {
      if (empty($build[$i]['#paragraph'])) {
        continue;
      }

      $paragraphContext = $this->contextPluginManager->createInstance(
        'io_builder_paragraph_field_context', $this->context->getConfiguration()
      );

      $paragraphContext->setDelta($i);

      $build[$i]['#io_builder']['#paragraph_context'] = $paragraphContext;
      $build[$i]['#io_builder']['add_more_top'] = $this->utility->buildAddMore($this->context, $i) + [
        '#position' => 'top',
      ];
    }

    // Add a final placeholder to add paragraphs.
    $build[$size] = [
      '#theme' => 'io_builder__placeholder',
      '#content' => $this->utility->buildAddMore($this->context) + [
          '#position' => 'center',
      ],
    ];
  }

  /**
   * Sets the paragraphs utility.
   *
   * @param \Drupal\io_builder_paragraphs\Utility\IoBuilderParagraphsUtility $utility
   *   The utility class.
   */
  public function setUtility(IoBuilderParagraphsUtility $utility): void {
    $this->utility = $utility;
  }

  /**
   * Sets the context plugin manager.
   *
   * @param \Drupal\io_builder\Plugin\IoBuilderContextPluginManager $contextPluginManager
   *   The context plugin manager.
   */
  public function setContextPluginManager(IoBuilderContextPluginManager $contextPluginManager): void {
    $this->contextPluginManager = $contextPluginManager;
  }

}
