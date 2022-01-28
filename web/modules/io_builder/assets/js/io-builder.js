(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.ioBuilder = {

    /**
     * {@inheritdoc}
     */
    attach: function (context, settings) {
      this.attachPanelListeners(context, settings);
      this.attachActionListeners(context, settings);
    },

    /**
     * Attaches event listeners to the IO builder panels.
     *
     * @param context
     *   The context
     * @param settings
     *   The settings
     */
    attachPanelListeners: function (context, settings) {
      var $panel = $(context).find('.io-builder--panel').once('panel_init');

      $panel.find('.close, .io-builder--panel__overlay').on('click', function() {
        console.log($panel);
        $(this).closest('.io-builder--panel').remove();
      });
    },

    /**
     * Attaches event listeners to the IO actions.
     *
     * @param context
     *   The context
     * @param settings
     *   The settings
     */
    attachActionListeners: function (context, settings) {
      var self = this;
      var $actions = $(context).find('a[data-io-builder-action="ajax_action"]').once('ajax_action_init');

      $actions.on('click', function(e) {
        e.preventDefault();
        var $element = $(this).parents('[data-io-builder-element]').first();

        var loadAjax = Drupal.ajax({
          url: $(this).attr('href'),
          type: "POST",
          submit: {
            'io_builder_context_tree': self.retrieveContextData($element)
          },
        });

        loadAjax.execute().done(function() {
          Drupal.attachBehaviors();
        });
      });
    },

    /**
     * Retrieves the context data for an element.
     *
     * @param $element
     *   The element.
     *
     * @returns {{parent: ({}|*), entity: ({}|*)}}
     *   An array which is sent with the requests.
     */
    retrieveContextData: function($element) {
      return {
        'parent': this.getParentData($element),
        'top_parent': this.getTopParentData($element),
        'entity': this.getIoBuilderData($element),
      };
    },

    /**
     * Gets last the parent IO builder data from an element.
     *
     * The parent is the top IO builder element.
     *
     * @param $element
     *   The element.
     *
     * @returns {{}|*}
     *   Contains the io builder data.
     */
    getTopParentData: function ($element) {
      var $parent = $element.parents('[data-io-builder-element]').last();

      if ($parent.length <= 0) {
        return {};
      }

      return this.getIoBuilderData($parent);
    },

    /**
     * Gets the parent IO builder data from an element.
     *
     * The parent is the top IO builder element.
     *
     * @param $element
     *   The element.
     *
     * @returns {{}|*}
     *   Contains the io builder data.
     */
    getParentData: function ($element) {
      var $parent = $element.parents('[data-io-builder-element]').first();

      if ($parent.length <= 0) {
        return {};
      }

      return this.getIoBuilderData($parent);
    },

    /**
     * Gets the IO Builder data from the element.
     *
     * @param $element
     *   The element.
     *
     * @returns {{}|*}
     *   Contains the io builder data.
     */
    getIoBuilderData: function($element) {
      var ioBuilderData = $element.data('io-builder-data');

      if (!ioBuilderData) {
        return {};
      }

      return ioBuilderData;
    }

  };
})(jQuery, Drupal, drupalSettings);
